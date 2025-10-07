<?php
require __DIR__ . '/bootstrap.php';

if (!Auth::check()) { redirect('/login.php'); }

$pdo = Database::pdo();
$error = null; $infoMsg = null;

// Ensure schema
try { $pdo->exec(file_get_contents(__DIR__ . '/../sql/schema.sql')); } catch (Throwable $e) {}

// Actions: create account
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  try {
    if ($action === 'create_account') {
      $name = trim($_POST['name'] ?? '');
      $bank_name = trim($_POST['bank_name'] ?? '');
      $number = trim($_POST['number'] ?? '');
      $initial_balance = (float)($_POST['initial_balance'] ?? 0);
      if (!$name) { throw new Exception('Informe o nome da conta.'); }
      $stmt = $pdo->prepare('INSERT INTO accounts (name, bank_name, number, initial_balance, is_active) VALUES (?,?,?,?,1)');
      $stmt->execute([$name, $bank_name ?: null, $number ?: null, $initial_balance]);
      $infoMsg = 'Conta financeira criada.';
    }
  } catch (Throwable $e) { $error = 'Erro: ' . $e->getMessage(); }
}

// Load accounts
$accounts = [];
try { $accounts = $pdo->query('SELECT * FROM accounts ORDER BY name')->fetchAll(); } catch (Throwable $e) { $error = $error ?: ('Erro ao carregar contas: ' . $e->getMessage()); }

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo page_title('Contas Financeiras'); ?></title>
  <?php include __DIR__ . '/partials/header.php'; ?>
</head>
<body>
<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Contas Financeiras</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreateAccount">Nova Conta</button>
  </div>

  <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
  <?php if ($infoMsg): ?><div class="alert alert-success"><?php echo htmlspecialchars($infoMsg); ?></div><?php endif; ?>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead><tr><th>#</th><th>Nome</th><th>Banco</th><th>Número</th><th>Saldo Inicial</th><th>Status</th><th>Ações</th></tr></thead>
        <tbody>
        <?php foreach ($accounts as $a): ?>
          <tr>
            <td><?php echo (int)$a['id']; ?></td>
            <td><?php echo htmlspecialchars($a['name']); ?></td>
            <td><?php echo htmlspecialchars($a['bank_name'] ?? '—'); ?></td>
            <td><?php echo htmlspecialchars($a['number'] ?? '—'); ?></td>
            <td>R$ <?php echo number_format((float)$a['initial_balance'], 2, ',', '.'); ?></td>
            <td><span class="badge bg-<?php echo ($a['is_active']??1)?'success':'secondary'; ?>"><?php echo ($a['is_active']??1)?'Ativa':'Inativa'; ?></span></td>
            <td class="text-nowrap">
              <a class="btn btn-sm btn-outline-light" href="/contas_financeiras_ledger.php?account_id=<?php echo (int)$a['id']; ?>">Movimentações</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (empty($accounts)): ?><div class="alert alert-info m-3">Nenhuma conta cadastrada.</div><?php endif; ?>
  </div>
</div>

<!-- Modal: Nova Conta -->
<div class="modal fade" id="modalCreateAccount" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Nova Conta Financeira</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <form method="post" action="/contas_financeiras.php">
        <input type="hidden" name="action" value="create_account">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Nome *</label><input class="form-control" name="name" required></div>
            <div class="col-md-3"><label class="form-label">Banco</label><input class="form-control" name="bank_name"></div>
            <div class="col-md-3"><label class="form-label">Número</label><input class="form-control" name="number"></div>
            <div class="col-md-3"><label class="form-label">Saldo Inicial *</label><input type="number" step="0.01" min="0" class="form-control" name="initial_balance" required></div>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div>
      </form>
    </div>
  </div>
}</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>