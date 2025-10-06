<?php
require __DIR__ . '/bootstrap.php';

if (!Auth::check()) { redirect('/login.php'); }

$pdo = Database::pdo();
$error = null; $infoMsg = null;

// Ensure schema
try { $pdo->exec(file_get_contents(__DIR__ . '/../sql/schema.sql')); } catch (Throwable $e) {}

// Actions: create, update, delete, toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  try {
    if ($action === 'create') {
      $name = trim($_POST['name'] ?? '');
      $description = trim($_POST['description'] ?? '');
      $classification = $_POST['classification'] ?? '';
      if (!$name) { throw new Exception('Nome é obrigatório.'); }
      if ($classification !== 'receita' && $classification !== 'despesa') { throw new Exception('Classificação inválida.'); }
      $stmt = $pdo->prepare('INSERT INTO account_types (name, description, classification, is_active) VALUES (?,?,?,1)');
      $stmt->execute([$name, $description ?: null, $classification]);
      $infoMsg = 'Tipo de conta criado.';
    } elseif ($action === 'update') {
      $id = (int)($_POST['id'] ?? 0);
      $name = trim($_POST['name'] ?? '');
      $description = trim($_POST['description'] ?? '');
      $classification = $_POST['classification'] ?? '';
      if ($id <= 0 || !$name) { throw new Exception('ID e nome são obrigatórios.'); }
      if ($classification !== 'receita' && $classification !== 'despesa') { throw new Exception('Classificação inválida.'); }
      $stmt = $pdo->prepare('UPDATE account_types SET name=?, description=?, classification=? WHERE id=?');
      $stmt->execute([$name, $description ?: null, $classification, $id]);
      $infoMsg = 'Tipo de conta atualizado.';
    } elseif ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) { throw new Exception('ID inválido.'); }
      $pdo->prepare('DELETE FROM account_types WHERE id=?')->execute([$id]);
      $infoMsg = 'Tipo de conta excluído.';
    } elseif ($action === 'toggle') {
      $id = (int)($_POST['id'] ?? 0);
      $is_active = (int)($_POST['is_active'] ?? 1);
      if ($id <= 0) { throw new Exception('ID inválido.'); }
      $pdo->prepare('UPDATE account_types SET is_active=? WHERE id=?')->execute([$is_active ? 1 : 0, $id]);
      $infoMsg = 'Status atualizado.';
    }
  } catch (Throwable $e) { $error = 'Erro: ' . $e->getMessage(); }
}

// Filters
$q = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');
$classFilter = trim($_GET['classification'] ?? '');

$sql = 'SELECT * FROM account_types WHERE 1=1';
$params = [];
if ($q) { $sql .= ' AND (name LIKE ? OR description LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($status !== '') { $sql .= ' AND is_active = ?'; $params[] = $status === 'active' ? 1 : 0; }
if ($classFilter !== '') { $sql .= ' AND classification = ?'; $params[] = $classFilter; }
$sql .= ' ORDER BY name ASC';

$rows = [];
try { $stmt = $pdo->prepare($sql); $stmt->execute($params); $rows = $stmt->fetchAll(); } catch (Throwable $e) { $error = 'Erro ao consultar: ' . $e->getMessage(); }

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo page_title('Tipos de Conta'); ?></title>
  <?php include __DIR__ . '/partials/header.php'; ?>
</head>
<body>
<main class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Tipos de Conta</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreate">Novo Tipo</button>
  </div>

  <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
  <?php if ($infoMsg): ?><div class="alert alert-info"><?php echo htmlspecialchars($infoMsg); ?></div><?php endif; ?>

  <form class="row g-2 mb-3" method="get">
    <div class="col-sm-4">
      <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" class="form-control" placeholder="Buscar por nome ou descrição">
    </div>
    <div class="col-sm-3">
      <select name="status" class="form-select">
        <option value="">Todos</option>
        <option value="active" <?php echo $status==='active'?'selected':''; ?>>Ativos</option>
        <option value="inactive" <?php echo $status==='inactive'?'selected':''; ?>>Inativos</option>
      </select>
    </div>
    <div class="col-sm-3">
      <select name="classification" class="form-select">
        <option value="">Todas as classificações</option>
        <option value="receita" <?php echo $classFilter==='receita'?'selected':''; ?>>Receita</option>
        <option value="despesa" <?php echo $classFilter==='despesa'?'selected':''; ?>>Despesa</option>
      </select>
    </div>
    <div class="col-sm-2">
      <button class="btn btn-outline-light w-100" type="submit">Filtrar</button>
    </div>
  </form>

  <div class="card">
    <div class="card-body p-0">
      <table class="table table-striped mb-0">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Descrição</th>
            <th>Classificação</th>
            <th>Status</th>
            <th class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo htmlspecialchars($row['description'] ?? ''); ?></td>
            <td>
              <?php if ($row['classification'] === 'receita'): ?>
                <span class="badge bg-info text-dark">Receita</span>
              <?php elseif ($row['classification'] === 'despesa'): ?>
                <span class="badge bg-danger">Despesa</span>
              <?php else: ?>
                <span class="badge bg-light text-dark">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($row['is_active']): ?>
                <span class="badge bg-success">Ativo</span>
              <?php else: ?>
                <span class="badge bg-secondary">Inativo</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#modalEdit" data-id="<?php echo $row['id']; ?>" data-name="<?php echo htmlspecialchars($row['name']); ?>" data-description="<?php echo htmlspecialchars($row['description'] ?? ''); ?>" data-classification="<?php echo htmlspecialchars($row['classification'] ?? 'despesa'); ?>">Editar</button>
              <button class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#modalToggle" data-id="<?php echo $row['id']; ?>" data-active="<?php echo (int)$row['is_active']; ?>"><?php echo $row['is_active'] ? 'Desativar' : 'Ativar'; ?></button>
              <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalDelete" data-id="<?php echo $row['id']; ?>">Excluir</button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Create Modal -->
  <div class="modal fade" id="modalCreate" tabindex="-1">
    <div class="modal-dialog">
      <form class="modal-content" method="post">
        <input type="hidden" name="action" value="create">
        <div class="modal-header"><h5 class="modal-title">Novo Tipo de Conta</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Nome</label><input type="text" name="name" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Descrição</label><textarea name="description" class="form-control" rows="3"></textarea></div>
          <div class="mb-3"><label class="form-label">Classificação *</label>
            <select name="classification" class="form-select" required>
              <option value="">Selecione</option>
              <option value="receita">Receita</option>
              <option value="despesa">Despesa</option>
            </select>
          </div>
        </div>
        <div class="modal-footer"><button class="btn btn-outline-light" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Salvar</button></div>
      </form>
    </div>
  </div>

  <!-- Edit Modal -->
  <div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
      <form class="modal-content" method="post">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="edit-id">
        <div class="modal-header"><h5 class="modal-title">Editar Tipo de Conta</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Nome</label><input type="text" name="name" id="edit-name" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Descrição</label><textarea name="description" id="edit-description" class="form-control" rows="3"></textarea></div>
          <div class="mb-3"><label class="form-label">Classificação *</label>
            <select name="classification" id="edit-classification" class="form-select" required>
              <option value="receita">Receita</option>
              <option value="despesa">Despesa</option>
            </select>
          </div>
        </div>
        <div class="modal-footer"><button class="btn btn-outline-light" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Atualizar</button></div>
      </form>
    </div>
  </div>

  <!-- Toggle Modal -->
  <div class="modal fade" id="modalToggle" tabindex="-1">
    <div class="modal-dialog">
      <form class="modal-content" method="post">
        <input type="hidden" name="action" value="toggle">
        <input type="hidden" name="id" id="toggle-id">
        <input type="hidden" name="is_active" id="toggle-active">
        <div class="modal-header"><h5 class="modal-title">Alterar Status</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <p id="toggle-text" class="mb-0"></p>
        </div>
        <div class="modal-footer"><button class="btn btn-outline-light" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Confirmar</button></div>
      </form>
    </div>
  </div>

  <!-- Delete Modal -->
  <div class="modal fade" id="modalDelete" tabindex="-1">
    <div class="modal-dialog">
      <form class="modal-content" method="post">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete-id">
        <div class="modal-header"><h5 class="modal-title">Excluir Tipo de Conta</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <p>Tem certeza que deseja excluir este tipo de conta?</p>
        </div>
        <div class="modal-footer"><button class="btn btn-outline-light" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-danger" type="submit">Excluir</button></div>
      </form>
    </div>
  </div>

  <?php include __DIR__ . '/partials/footer.php'; ?>
</main>

<script>
document.getElementById('modalEdit')?.addEventListener('show.bs.modal', function (event) {
  const btn = event.relatedTarget;
  document.getElementById('edit-id').value = btn.getAttribute('data-id');
  document.getElementById('edit-name').value = btn.getAttribute('data-name');
  document.getElementById('edit-description').value = btn.getAttribute('data-description') || '';
  const cls = btn.getAttribute('data-classification') || 'despesa';
  const select = document.getElementById('edit-classification');
  if (select) { select.value = cls; }
});

document.getElementById('modalToggle')?.addEventListener('show.bs.modal', function (event) {
  const btn = event.relatedTarget;
  const id = btn.getAttribute('data-id');
  const active = btn.getAttribute('data-active') === '1';
  document.getElementById('toggle-id').value = id;
  document.getElementById('toggle-active').value = active ? 0 : 1;
  document.getElementById('toggle-text').textContent = active ? 'Desativar este tipo de conta?' : 'Ativar este tipo de conta?';
});

document.getElementById('modalDelete')?.addEventListener('show.bs.modal', function (event) {
  const btn = event.relatedTarget;
  document.getElementById('delete-id').value = btn.getAttribute('data-id');
});
</script>
</body>
</html>