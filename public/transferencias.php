<?php
require __DIR__ . '/bootstrap.php';

if (!Auth::check()) { redirect('/login.php'); }

$pdo = Database::pdo();
$error = null; $infoMsg = null;

// Ensure schema
try { $pdo->exec(file_get_contents(__DIR__ . '/../sql/schema.sql')); } catch (Throwable $e) {}

// Carregar contas ativas para seleção
$accounts = [];
try {
    $stmt = $pdo->query('SELECT id, name, is_active FROM accounts WHERE is_active = 1 ORDER BY name');
    $accounts = $stmt->fetchAll();
} catch (Throwable $e) { $error = $error ?: ('Erro ao carregar contas: ' . $e->getMessage()); }

// Handler de transferência (apenas afeta os saldos via ledger)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'transfer') {
            $from_id = (int)($_POST['from_account_id'] ?? 0);
            $to_id = (int)($_POST['to_account_id'] ?? 0);
            $amount = (float)($_POST['amount'] ?? 0);
            $desc = trim($_POST['description'] ?? '');

            if ($from_id <= 0 || $to_id <= 0) { throw new Exception('Selecione as contas de origem e destino.'); }
            if ($from_id === $to_id) { throw new Exception('As contas de origem e destino devem ser diferentes.'); }
            if ($amount <= 0) { throw new Exception('Informe um valor maior que zero.'); }

            // Buscar nomes das contas para descrever a transferência
            $stmt = $pdo->prepare('SELECT id, name FROM accounts WHERE id IN (?, ?)');
            $stmt->execute([$from_id, $to_id]);
            $mapNames = [];
            foreach ($stmt->fetchAll() as $row) { $mapNames[(int)$row['id']] = (string)$row['name']; }
            if (empty($mapNames[$from_id]) || empty($mapNames[$to_id])) { throw new Exception('Contas inválidas.'); }
            $fromName = $mapNames[$from_id];
            $toName = $mapNames[$to_id];

            // Inserir duas movimentações no livro-razão: débito na origem, crédito no destino
            $pdo->beginTransaction();
            try {
                $ins = $pdo->prepare('INSERT INTO account_ledgers (account_id, movement_type, amount, description, related_table, related_id) VALUES (?,?,?,?,?,NULL)');
                // Débito na conta de origem
                $ins->execute([$from_id, 'debit', $amount, ($desc ? $desc . ' — ' : '') . 'Transferência para ' . $toName, 'transfer']);
                // Crédito na conta de destino
                $ins->execute([$to_id, 'credit', $amount, ($desc ? $desc . ' — ' : '') . 'Transferência de ' . $fromName, 'transfer']);
                $pdo->commit();
                $infoMsg = 'Transferência registrada com sucesso.';
            } catch (Throwable $inner) {
                $pdo->rollBack();
                throw $inner;
            }
        }
    } catch (Throwable $e) { $error = 'Erro: ' . $e->getMessage(); }
}

// Listar últimas movimentações de transferências (ambas as pontas)
$recent = [];
try {
    $stmt = $pdo->query("SELECT l.id, l.account_id, a.name AS account_name, l.movement_type, l.amount, l.description, l.created_at
                         FROM account_ledgers l
                         JOIN accounts a ON a.id = l.account_id
                         WHERE l.related_table = 'transfer'
                         ORDER BY l.id DESC
                         LIMIT 20");
    $recent = $stmt->fetchAll();
} catch (Throwable $e) {}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo page_title('Transferências'); ?></title>
  <?php include __DIR__ . '/partials/header.php'; ?>
  <style>
    .transfer-card.card { border-radius: 12px; }
  </style>
</head>
<body>
<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Transferências entre Contas</h3>
  </div>

  <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
  <?php if ($infoMsg): ?><div class="alert alert-success"><?php echo htmlspecialchars($infoMsg); ?></div><?php endif; ?>

  <div class="card transfer-card mb-4">
    <div class="card-body">
      <form method="post" action="/transferencias.php">
        <input type="hidden" name="action" value="transfer">
        <div class="row g-3">
          <div class="col-md-5">
            <label class="form-label">Conta de Origem *</label>
            <select name="from_account_id" class="form-select" required>
              <option value="">—</option>
              <?php foreach($accounts as $a){ echo '<option value="'.(int)$a['id'].'">'.htmlspecialchars($a['name']).'</option>'; } ?>
            </select>
          </div>
          <div class="col-md-5">
            <label class="form-label">Conta de Destino *</label>
            <select name="to_account_id" class="form-select" required>
              <option value="">—</option>
              <?php foreach($accounts as $a){ echo '<option value="'.(int)$a['id'].'">'.htmlspecialchars($a['name']).'</option>'; } ?>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Valor *</label>
            <input type="number" step="0.01" min="0.01" class="form-control" name="amount" required>
          </div>
          <div class="col-12">
            <label class="form-label">Observações</label>
            <input type="text" class="form-control" name="description" placeholder="Opcional">
          </div>
        </div>
        <div class="mt-3">
          <button type="submit" class="btn btn-primary">Transferir</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead><tr><th>#</th><th>Conta</th><th>Tipo</th><th>Valor</th><th>Descrição</th><th>Data</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $r): ?>
          <tr>
            <td><?php echo (int)$r['id']; ?></td>
            <td><?php echo htmlspecialchars($r['account_name']); ?></td>
            <td><span class="badge bg-<?php echo ($r['movement_type']==='debit')?'danger':'success'; ?>"><?php echo htmlspecialchars($r['movement_type']); ?></span></td>
            <td>R$ <?php echo number_format((float)$r['amount'], 2, ',', '.'); ?></td>
            <td><?php echo htmlspecialchars($r['description'] ?? '—'); ?></td>
            <td><?php echo htmlspecialchars($r['created_at'] ?? '—'); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (empty($recent)): ?><div class="alert alert-info m-3">Nenhuma transferência registrada.</div><?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>