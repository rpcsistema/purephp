<?php
require __DIR__ . '/bootstrap.php';
if (!Auth::check()) { redirect('/login.php'); }
$pdo = Database::pdo();
$error = null;

$account_id = (int)($_GET['account_id'] ?? 0);
if ($account_id <= 0) { $error = 'Conta inválida.'; }

$account = null;
if (!$error) {
  try {
    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE id=?');
    $stmt->execute([$account_id]);
    $account = $stmt->fetch();
    if (!$account) { $error = 'Conta não encontrada.'; }
  } catch (Throwable $e) { $error = 'Erro: ' . $e->getMessage(); }
}

$rows = [];
if (!$error) {
  try {
    $stmt = $pdo->prepare('SELECT * FROM account_ledgers WHERE account_id=? ORDER BY id DESC');
    $stmt->execute([$account_id]);
    $rows = $stmt->fetchAll();
  } catch (Throwable $e) { $error = 'Erro: ' . $e->getMessage(); }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo page_title('Movimentações'); ?></title>
  <?php include __DIR__ . '/partials/header.php'; ?>
</head>
<body>
<div class="container mt-4">
  <a href="/contas_financeiras.php" class="btn btn-outline-light mb-3">← Voltar</a>
  <h3>Movimentações da Conta: <?php echo htmlspecialchars($account['name'] ?? '—'); ?></h3>
  <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead><tr><th>#</th><th>Tipo</th><th>Valor</th><th>Descrição</th><th>Origem</th><th>Data</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo (int)$r['id']; ?></td>
            <td><span class="badge bg-<?php echo $r['movement_type']==='debit'?'danger':'success'; ?>"><?php echo htmlspecialchars($r['movement_type']); ?></span></td>
            <td>R$ <?php echo number_format((float)$r['amount'], 2, ',', '.'); ?></td>
            <td><?php echo htmlspecialchars($r['description'] ?? '—'); ?></td>
            <td><?php echo htmlspecialchars(($r['related_table'] ?? '—') . '#' . ($r['related_id'] ?? '—')); ?></td>
            <td><?php echo htmlspecialchars($r['created_at'] ?? '—'); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (empty($rows)): ?><div class="alert alert-info m-3">Nenhuma movimentação registrada.</div><?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>