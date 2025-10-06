<?php
require __DIR__ . '/bootstrap.php';

if (!Auth::check()) {
    redirect('/login.php');
}

$pdo = Database::pdo();
$error = null;
$infoMsg = null;

// Processar criação/edição via POST de modais
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $tax_id = trim($_POST['tax_id'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $zip = trim($_POST['zip'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    try {
        // Garantir tabela
        $pdo->exec(file_get_contents(__DIR__ . '/../sql/schema.sql'));
    } catch (Throwable $e) {}

    if ($action === 'create') {
        if (!$name || !$email) {
            $error = 'Nome e e-mail são obrigatórios.';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO suppliers (name, email, phone, company, tax_id, address, city, state, zip, notes) VALUES (?,?,?,?,?,?,?,?,?,?)');
                $stmt->execute([$name, $email, $phone ?: null, $company ?: null, $tax_id ?: null, $address ?: null, $city ?: null, $state ?: null, $zip ?: null, $notes ?: null]);
                $infoMsg = 'Fornecedor cadastrado com sucesso!';
            } catch (Throwable $e) {
                $error = 'Erro ao salvar: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $error = 'ID inválido.';
        } elseif (!$name || !$email) {
            $error = 'Nome e e-mail são obrigatórios.';
        } else {
            try {
                $stmt = $pdo->prepare('UPDATE suppliers SET name=?, email=?, phone=?, company=?, tax_id=?, address=?, city=?, state=?, zip=?, notes=? WHERE id=?');
                $stmt->execute([$name, $email, $phone ?: null, $company ?: null, $tax_id ?: null, $address ?: null, $city ?: null, $state ?: null, $zip ?: null, $notes ?: null, $id]);
                $infoMsg = 'Fornecedor atualizado com sucesso!';
            } catch (Throwable $e) {
                $error = 'Erro ao atualizar: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare('DELETE FROM suppliers WHERE id = ?');
                $stmt->execute([$id]);
                $infoMsg = 'Fornecedor excluído.';
            } catch (Throwable $e) {
                $error = 'Erro ao excluir: ' . $e->getMessage();
            }
        }
    }
}

// Filtros avançados
$q = trim($_GET['q'] ?? '');
$city = trim($_GET['city'] ?? '');
$state = trim($_GET['state'] ?? '');

$sql = 'SELECT id, name, email, phone, company, city, state FROM suppliers WHERE 1=1';
$params = [];
if ($q) {
    $sql .= ' AND (name LIKE ? OR email LIKE ? OR company LIKE ?)';
    $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";
}
if ($city) { $sql .= ' AND city LIKE ?'; $params[] = "%$city%"; }
if ($state) { $sql .= ' AND state LIKE ?'; $params[] = "%$state%"; }
$sql .= ' ORDER BY id DESC';

$suppliers = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $suppliers = $stmt->fetchAll();
} catch (Throwable $e) {
    $error = 'Erro ao consultar: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo page_title('Fornecedores'); ?></title>
    <?php include __DIR__ . '/partials/header.php'; ?>
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Fornecedores</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreate">Novo Fornecedor</button>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if ($infoMsg): ?><div class="alert alert-success"><?php echo htmlspecialchars($infoMsg); ?></div><?php endif; ?>

    <form class="card p-3 mb-3" method="get">
        <div class="row g-2">
            <div class="col-md-4">
                <label class="form-label">Busca</label>
                <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Nome, e-mail ou empresa">
            </div>
            <div class="col-md-3">
                <label class="form-label">Cidade</label>
                <input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($city); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Estado</label>
                <input type="text" class="form-control" name="state" value="<?php echo htmlspecialchars($state); ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-outline-light w-100" type="submit">Filtrar</button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th><th>Nome</th><th>E-mail</th><th>Telefone</th><th>Empresa</th><th>Cidade/UF</th><th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($suppliers as $s): ?>
                    <tr>
                        <td><?php echo (int)$s['id']; ?></td>
                        <td><?php echo htmlspecialchars($s['name']); ?></td>
                        <td><?php echo htmlspecialchars($s['email']); ?></td>
                        <td><?php echo htmlspecialchars($s['phone'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($s['company'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars(($s['city'] ?? '') . ($s['state'] ? ' / ' . $s['state'] : '')); ?></td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEdit" data-id="<?php echo (int)$s['id']; ?>" data-name="<?php echo htmlspecialchars($s['name']); ?>" data-email="<?php echo htmlspecialchars($s['email']); ?>" data-phone="<?php echo htmlspecialchars($s['phone'] ?? ''); ?>" data-company="<?php echo htmlspecialchars($s['company'] ?? ''); ?>">Editar</button>
                            <form method="post" action="/fornecedores.php" style="display:inline" onsubmit="return confirm('Excluir este fornecedor?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (empty($suppliers)): ?><div class="alert alert-info m-3">Nenhum fornecedor encontrado.</div><?php endif; ?>
    </div>
</div>

<!-- Modal: Novo Fornecedor -->
<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Cadastrar Fornecedor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="/fornecedores.php">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Nome *</label><input class="form-control" name="name" required></div>
          <div class="col-md-6"><label class="form-label">E-mail *</label><input type="email" class="form-control" name="email" required></div>
          <div class="col-md-4"><label class="form-label">Telefone</label><input class="form-control" name="phone"></div>
          <div class="col-md-4"><label class="form-label">Empresa</label><input class="form-control" name="company"></div>
          <div class="col-md-4"><label class="form-label">CNPJ/CPF</label><input class="form-control" name="tax_id"></div>
          <div class="col-md-6"><label class="form-label">Endereço</label><input class="form-control" name="address"></div>
          <div class="col-md-3"><label class="form-label">Cidade</label><input class="form-control" name="city"></div>
          <div class="col-md-2"><label class="form-label">Estado</label><input class="form-control" name="state"></div>
          <div class="col-md-1"><label class="form-label">CEP</label><input class="form-control" name="zip"></div>
          <div class="col-12"><label class="form-label">Observações</label><textarea class="form-control" name="notes" rows="2"></textarea></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar</button>
      </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Editar Fornecedor -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Editar Fornecedor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="/fornecedores.php">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="edit-id">
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Nome *</label><input class="form-control" name="name" id="edit-name" required></div>
          <div class="col-md-6"><label class="form-label">E-mail *</label><input type="email" class="form-control" name="email" id="edit-email" required></div>
          <div class="col-md-4"><label class="form-label">Telefone</label><input class="form-control" name="phone" id="edit-phone"></div>
          <div class="col-md-4"><label class="form-label">Empresa</label><input class="form-control" name="company" id="edit-company"></div>
          <div class="col-md-4"><label class="form-label">CNPJ/CPF</label><input class="form-control" name="tax_id" id="edit-tax_id"></div>
          <div class="col-md-6"><label class="form-label">Endereço</label><input class="form-control" name="address" id="edit-address"></div>
          <div class="col-md-3"><label class="form-label">Cidade</label><input class="form-control" name="city" id="edit-city"></div>
          <div class="col-md-2"><label class="form-label">Estado</label><input class="form-control" name="state" id="edit-state"></div>
          <div class="col-md-1"><label class="form-label">CEP</label><input class="form-control" name="zip" id="edit-zip"></div>
          <div class="col-12"><label class="form-label">Observações</label><textarea class="form-control" name="notes" id="edit-notes" rows="2"></textarea></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
      </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('modalEdit')?.addEventListener('show.bs.modal', function (event) {
  const button = event.relatedTarget;
  const id = button.getAttribute('data-id');
  const name = button.getAttribute('data-name');
  const email = button.getAttribute('data-email');
  const phone = button.getAttribute('data-phone');
  const company = button.getAttribute('data-company');
  document.getElementById('edit-id').value = id;
  document.getElementById('edit-name').value = name;
  document.getElementById('edit-email').value = email;
  document.getElementById('edit-phone').value = phone;
  document.getElementById('edit-company').value = company;
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>