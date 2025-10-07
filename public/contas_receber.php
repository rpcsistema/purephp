<?php
require __DIR__ . '/bootstrap.php';

if (!Auth::check()) { redirect('/login.php'); }

$pdo = Database::pdo();
$error = null; $infoMsg = null;

// Ensure schema
try { $pdo->exec(file_get_contents(__DIR__ . '/../sql/schema.sql')); } catch (Throwable $e) {}

// Actions: create (lançamento), settle (baixa), delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  try {
    if ($action === 'create') {
      $description = trim($_POST['description'] ?? '');
      $amount = (float)($_POST['amount'] ?? 0);
      $due_date = trim($_POST['due_date'] ?? '');
      $client_id = (int)($_POST['client_id'] ?? 0);
      $account_type_id = (int)($_POST['account_type_id'] ?? 0);
      $account_id = (int)($_POST['account_id'] ?? 0);
      $category = trim($_POST['category'] ?? '');
      $notes = trim($_POST['notes'] ?? '');
      if (!$description || $amount <= 0 || !$due_date) { throw new Exception('Preencha descrição, valor e vencimento.'); }
      if ($account_id <= 0) { throw new Exception('Selecione uma conta financeira.'); }
      $stmt = $pdo->prepare('INSERT INTO receivables (client_id, account_type_id, account_id, description, category, due_date, amount, status, notes) VALUES (?,?,?,?,?,?,?,"open",?)');
      $stmt->execute([$client_id ?: null, $account_type_id ?: null, $account_id, $description, $category ?: null, $due_date, $amount, $notes ?: null]);
      $infoMsg = 'Recebível lançado.';
    } elseif ($action === 'settle') {
      $id = (int)($_POST['id'] ?? 0);
      $receipt_method = trim($_POST['receipt_method'] ?? '');
      $settle_account_id = (int)($_POST['settle_account_id'] ?? 0);
      if ($id <= 0) { throw new Exception('ID inválido.'); }
      if ($settle_account_id <= 0) { throw new Exception('Selecione a conta utilizada no recebimento.'); }
      $stmt = $pdo->prepare('UPDATE receivables SET status="received", received_at=NOW(), receipt_method=? WHERE id=?');
      $stmt->execute([$receipt_method ?: null, $id]);
      // Registrar entrada no razão usando a conta selecionada
      try {
        $ps = $pdo->prepare('SELECT description, amount FROM receivables WHERE id=?');
        $ps->execute([$id]);
        $rec = $ps->fetch();
        if ($rec) {
          $desc = 'Recebimento: ' . (string)($rec['description'] ?? '');
          $amt = (float)($rec['amount'] ?? 0);
          $accId = (int)$settle_account_id;
          $ls = $pdo->prepare('INSERT INTO account_ledgers (account_id, movement_type, amount, description, related_table, related_id) VALUES (?,?,?,?,?,?)');
          $ls->execute([$accId, 'credit', $amt, $desc ?: null, 'receivables', $id]);
        }
      } catch (Throwable $e) { /* não interromper baixa em caso de erro no razão */ }
      $infoMsg = 'Recebimento baixado com sucesso.';
    } elseif ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) { throw new Exception('ID inválido.'); }
      $pdo->prepare('DELETE FROM receivables WHERE id=?')->execute([$id]);
      $infoMsg = 'Lançamento excluído.';
    }
  } catch (Throwable $e) { $error = 'Erro: ' . $e->getMessage(); }
}

// Filters
$q = trim($_GET['q'] ?? '');
$status = array_key_exists('status', $_GET) ? trim($_GET['status']) : 'open';
$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');
$classification = trim($_GET['classification'] ?? '');

// Evitar erro caso coluna account_type_id não exista (ambientes desatualizados)
$dbName = '';
try { $dbName = (string)($pdo->query('SELECT DATABASE()')->fetchColumn() ?: ''); } catch (Throwable $e) {}
$hasAcctTypeCol = false;
if ($dbName) {
  try {
    $chk = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $chk->execute([$dbName, 'receivables', 'account_type_id']);
    $hasAcctTypeCol = ((int)$chk->fetchColumn() > 0);
  } catch (Throwable $e) {}
}

$sql = 'SELECT r.*, c.name AS client_name' . ($hasAcctTypeCol ? ', at.name AS account_type_name, at.classification AS account_type_classification ' : ' ') .
       'FROM receivables r LEFT JOIN clients c ON c.id = r.client_id ' . ($hasAcctTypeCol ? 'LEFT JOIN account_types at ON at.id = r.account_type_id ' : '') . 'WHERE 1=1';
$params = [];
if ($q) { $sql .= ' AND (r.description LIKE ? OR c.name LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($status) { $sql .= ' AND r.status = ?'; $params[] = $status; }
if ($from) { $sql .= ' AND r.due_date >= ?'; $params[] = $from; }
if ($to) { $sql .= ' AND r.due_date <= ?'; $params[] = $to; }
$account_type_id_filter = (int)($_GET['account_type_id'] ?? 0);
if ($hasAcctTypeCol && $account_type_id_filter) { $sql .= ' AND r.account_type_id = ?'; $params[] = $account_type_id_filter; }
if ($hasAcctTypeCol && ($classification === 'receita' || $classification === 'despesa')) { $sql .= ' AND at.classification = ?'; $params[] = $classification; }
$sql .= ' ORDER BY r.due_date ASC';

$rows = [];
try { $stmt = $pdo->prepare($sql); $stmt->execute($params); $rows = $stmt->fetchAll(); } catch (Throwable $e) { $error = 'Erro ao consultar: ' . $e->getMessage(); }

// Load clients for selects
$clients = [];
try { $clients = $pdo->query('SELECT id, name FROM clients ORDER BY name')->fetchAll(); } catch (Throwable $e) {}

// Load account types for selects
$accountTypes = [];
try { $accountTypes = $pdo->query('SELECT id, name FROM account_types WHERE is_active = 1 ORDER BY name')->fetchAll(); } catch (Throwable $e) {}

// Load financial accounts for selects
$accounts = [];
try { $accounts = $pdo->query('SELECT id, name FROM accounts WHERE is_active = 1 ORDER BY name')->fetchAll(); } catch (Throwable $e) {}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo page_title('Contas a Receber'); ?></title>
  <?php include __DIR__ . '/partials/header.php'; ?>
</head>
<body>
<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Contas a Receber</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreate">Novo Lançamento</button>
  </div>

  <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
  <?php if ($infoMsg): ?><div class="alert alert-success"><?php echo htmlspecialchars($infoMsg); ?></div><?php endif; ?>

  <form class="card p-3 mb-3 filters-compact filters-xs" method="get">
    <div class="row g-2">
      <div class="col-md-4"><label class="form-label">Busca</label><input name="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="Descrição ou cliente"></div>
      <div class="col-md-2"><label class="form-label">Status</label>
        <select class="form-select" name="status">
          <option value="">Todos</option>
          <option value="open" <?php echo $status==='open'?'selected':''; ?>>Em aberto</option>
          <option value="received" <?php echo $status==='received'?'selected':''; ?>>Recebido</option>
          <option value="cancelled" <?php echo $status==='cancelled'?'selected':''; ?>>Cancelado</option>
        </select>
      </div>
      <div class="col-md-2"><label class="form-label">De</label><input type="date" name="from" class="form-control" value="<?php echo htmlspecialchars($from); ?>"></div>
      <div class="col-md-2"><label class="form-label">Até</label><input type="date" name="to" class="form-control" value="<?php echo htmlspecialchars($to); ?>"></div>
      <div class="col-md-2"><label class="form-label">Tipo de Conta</label>
        <select class="form-select" name="account_type_id">
          <option value="">Todos</option>
          <?php foreach($accountTypes as $t){ $sel = ($account_type_id_filter===(int)$t['id'])?'selected':''; echo '<option value="'.(int)$t['id'].'" '.$sel.'>'.htmlspecialchars($t['name']).'</option>'; } ?>
        </select>
      </div>
      <div class="col-md-2"><label class="form-label">Classificação</label>
        <select class="form-select" name="classification">
          <option value="">Todas</option>
          <option value="receita" <?php echo $classification==='receita'?'selected':''; ?>>Receita</option>
          <option value="despesa" <?php echo $classification==='despesa'?'selected':''; ?>>Despesa</option>
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-light w-100" type="submit">Filtrar</button></div>
    </div>
  </form>

  <!-- Resumo sticky com totais e chips de status -->
  <div class="card p-3 summary-sticky summary-compact summary-xs sticky-xs mb-3">
    <?php
      $totOpen = $pdo->query("SELECT COALESCE(SUM(amount),0) AS t FROM receivables WHERE status='open'")->fetch()['t'] ?? 0;
      $totReceived = $pdo->query("SELECT COALESCE(SUM(amount),0) AS t FROM receivables WHERE status='received'")->fetch()['t'] ?? 0;
      $totAll = (float)$totOpen + (float)$totReceived;
      $pctReceived = $totAll > 0 ? ($totReceived / $totAll) : 0;
      $pctOpen = $totAll > 0 ? ($totOpen / $totAll) : 0;
      $cntOpen = (int)($pdo->query("SELECT COUNT(*) FROM receivables WHERE status='open'")->fetchColumn() ?: 0);
      $cntReceived = (int)($pdo->query("SELECT COUNT(*) FROM receivables WHERE status='received'")->fetchColumn() ?: 0);
      $cntCancelled = (int)($pdo->query("SELECT COUNT(*) FROM receivables WHERE status='cancelled'")->fetchColumn() ?: 0);
      $cntAll = $cntOpen + $cntReceived + $cntCancelled;
    ?>
    <div class="status-chips">
      <a href="/contas_receber.php" class="status-chip <?php echo $status? '': 'active'; ?>">Todas <span class="chip-count" title="Quantidade total de lançamentos"><?php echo $cntAll; ?></span></a>
      <a href="/contas_receber.php?status=open" class="status-chip <?php echo $status==='open'? 'active': ''; ?>">Em aberto <span class="chip-count" title="Lançamentos ainda não recebidos"><?php echo $cntOpen; ?></span></a>
      <a href="/contas_receber.php?status=received" class="status-chip <?php echo $status==='received'? 'active': ''; ?>">Recebidas <span class="chip-count" title="Lançamentos baixados/recebidos"><?php echo $cntReceived; ?></span></a>
      <a href="/contas_receber.php?status=cancelled" class="status-chip <?php echo $status==='cancelled'? 'active': ''; ?>">Canceladas <span class="chip-count" title="Lançamentos cancelados"><?php echo $cntCancelled; ?></span></a>
    </div>
    <div class="ap-summary">
      <div class="ap-tile ap-open" title="Soma de recebíveis em aberto">
        <div class="ap-label">Em aberto</div>
        <div class="ap-value">R$ <?php echo number_format((float)$totOpen, 2, ',', '.'); ?></div>
      </div>
      <div class="ap-tile ap-paid" title="Soma de recebíveis recebidos">
        <div class="ap-label">Recebidas</div>
        <div class="ap-value">R$ <?php echo number_format((float)$totReceived, 2, ',', '.'); ?></div>
      </div>
      <div>
        <div class="ap-progress" title="Composição entre recebidas e em aberto">
          <div class="ap-progress-bar">
            <div class="ap-progress-paid" style="width: <?php echo round($pctReceived*100, 1); ?>%" title="Recebidas: <?php echo round($pctReceived*100, 1); ?>%"></div>
            <div class="ap-progress-open" style="width: <?php echo round($pctOpen*100, 1); ?>%" title="Em aberto: <?php echo round($pctOpen*100, 1); ?>%"></div>
          </div>
          <div class="ap-progress-meta">
            <div>Recebidas: <?php echo round($pctReceived*100, 1); ?>%</div>
            <div>Em aberto: <?php echo round($pctOpen*100, 1); ?>%</div>
            <div>Total: <strong>R$ <?php echo number_format($totAll, 2, ',', '.'); ?></strong></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle table-compact table-xs">
        <thead><tr><th>#</th><th>Vencimento</th><th>Descrição</th><th>Cliente</th><th>Tipo</th><th>Classificação</th><th>Valor</th><th>Status</th><th>Ações</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo (int)$r['id']; ?></td>
            <td>
              <?php
                $dt = strtotime($r['due_date'] ?? '');
                echo $dt ? htmlspecialchars(date('d/m/Y', $dt)) : '—';
                $today = strtotime(date('Y-m-d'));
                if ($dt && (($r['status'] ?? '') === 'open')) {
                  if ($dt < $today) {
                    echo ' <span class="badge bg-danger" title="Vencido">Vencido</span>';
                  } elseif ($dt === $today) {
                    echo ' <span class="badge bg-warning text-dark" title="Vence hoje">Hoje</span>';
                  }
                }
              ?>
            </td>
            <td><?php echo htmlspecialchars($r['description']); ?></td>
            <td><?php echo htmlspecialchars($r['client_name'] ?? '—'); ?></td>
            <td><?php echo htmlspecialchars($r['account_type_name'] ?? '—'); ?></td>
            <td>
              <?php if (($r['account_type_classification'] ?? null) === 'receita'): ?>
                <span class="badge bg-info text-dark">Receita</span>
              <?php elseif (($r['account_type_classification'] ?? null) === 'despesa'): ?>
                <span class="badge bg-danger">Despesa</span>
              <?php else: ?>
                <span class="badge bg-light text-dark">—</span>
              <?php endif; ?>
            </td>
            <td>R$ <?php echo number_format((float)$r['amount'], 2, ',', '.'); ?></td>
            <td><span class="badge bg-<?php echo $r['status']==='received'?'success':($r['status']==='cancelled'?'secondary':'warning'); ?>"><?php echo htmlspecialchars($r['status']); ?></span></td>
            <td class="text-nowrap">
              <?php if ($r['status']==='open'): ?>
              <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalSettle" data-id="<?php echo (int)$r['id']; ?>">Baixa</button>
              <?php endif; ?>
              <form method="post" action="/contas_receber.php" style="display:inline" onsubmit="return confirm('Excluir lançamento?');">
                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (empty($rows)): ?><div class="alert alert-info m-3">Nenhum lançamento encontrado.</div><?php endif; ?>
  </div>

  <!-- Resumo antigo removido (substituído pelo sticky acima) -->
</div>

<!-- Modal: Novo Lançamento -->
<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Novo Lançamento</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <form method="post" action="/contas_receber.php">
        <input type="hidden" name="action" value="create">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Descrição *</label><input class="form-control" name="description" required></div>
            <div class="col-md-3"><label class="form-label">Valor *</label><input type="number" step="0.01" min="0" class="form-control" name="amount" required></div>
            <div class="col-md-3"><label class="form-label">Vencimento *</label><input type="date" class="form-control" name="due_date" required></div>
            <div class="col-md-6"><label class="form-label">Cliente</label>
              <select name="client_id" class="form-select">
                <option value="">—</option>
                <?php foreach($clients as $c){ echo '<option value="'.(int)$c['id'].'">'.htmlspecialchars($c['name']).'</option>'; } ?>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label">Tipo de Conta</label>
              <select name="account_type_id" class="form-select">
                <option value="">—</option>
                <?php foreach($accountTypes as $t){ echo '<option value="'.(int)$t['id'].'">'.htmlspecialchars($t['name']).'</option>'; } ?>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label">Conta Financeira *</label>
              <select name="account_id" class="form-select" required>
                <option value="">—</option>
                <?php foreach($accounts as $a){ echo '<option value="'.(int)$a['id'].'">'.htmlspecialchars($a['name']).'</option>'; } ?>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label">Categoria</label><input class="form-control" name="category"></div>
            <div class="col-12"><label class="form-label">Observações</label><textarea class="form-control" name="notes" rows="2"></textarea></div>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Baixa -->
<div class="modal fade" id="modalSettle" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Baixa de Recebimento</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <form method="post" action="/contas_receber.php">
        <input type="hidden" name="action" value="settle">
        <input type="hidden" name="id" id="settle-id">
        <div class="modal-body">
          <label class="form-label">Forma de recebimento</label>
          <select class="form-select" name="receipt_method">
            <option value="cash">Dinheiro</option>
            <option value="bank_transfer">Transferência</option>
            <option value="pix">PIX</option>
            <option value="other">Outro</option>
          </select>
          <div class="mt-3">
            <label class="form-label">Conta utilizada no recebimento *</label>
            <select class="form-select" name="settle_account_id" required>
              <option value="">—</option>
              <?php foreach($accounts as $a){ echo '<option value="'.(int)$a['id'].'">'.htmlspecialchars($a['name']).'</option>'; } ?>
            </select>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success">Confirmar Baixa</button></div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('modalSettle')?.addEventListener('show.bs.modal', function (event) {
  const button = event.relatedTarget; const id = button.getAttribute('data-id');
  document.getElementById('settle-id').value = id;
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>