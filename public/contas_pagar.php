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
      $supplier_id = (int)($_POST['supplier_id'] ?? 0);
      $account_type_id = (int)($_POST['account_type_id'] ?? 0);
      $account_id = (int)($_POST['account_id'] ?? 0);
      $category = trim($_POST['category'] ?? '');
      $notes = trim($_POST['notes'] ?? '');
      $parcel_generate = (string)($_POST['parcel_generate'] ?? '0') === '1';

      if ($account_id <= 0) { throw new Exception('Selecione uma conta financeira.'); }
      if (!$description) { throw new Exception('Preencha a descrição.'); }

      if ($parcel_generate) {
        // Se o preview foi editado: aceitar arrays parcel_amount[] e parcel_due[]
        $parcelAmounts = isset($_POST['parcel_amount']) && is_array($_POST['parcel_amount']) ? array_values($_POST['parcel_amount']) : [];
        $parcelDues = isset($_POST['parcel_due']) && is_array($_POST['parcel_due']) ? array_values($_POST['parcel_due']) : [];
        $count = (int)($_POST['parcel_count'] ?? 0);
        if ($count > 0 && count($parcelAmounts) === $count && count($parcelDues) === $count) {
          // validar e inserir conforme arrays fornecidos
          $stmt = $pdo->prepare('INSERT INTO payables (supplier_id, account_type_id, account_id, description, category, due_date, amount, status, notes) VALUES (?,?,?,?,?,?,?,"open",?)');
          for ($i=0; $i<$count; $i++) {
            $due = trim((string)$parcelDues[$i]);
            $amt = (float)$parcelAmounts[$i];
            if ($amt <= 0 || !$due) { throw new Exception('Parcelas inválidas: verifique valores e vencimentos.'); }
            $descParc = $description . ' (Parcela ' . ($i+1) . '/' . $count . ')';
            $stmt->execute([$supplier_id ?: null, $account_type_id ?: null, $account_id, $descParc, $category ?: null, $due, $amt, $notes ?: null]);
          }
          $infoMsg = 'Parcelamento lançado: ' . $count . ' parcelas.';
        } else {
          // fallback para geração automática (backward-compatible)
          $total_amount = (float)($_POST['total_amount'] ?? 0);
          $installments = max(1, (int)($_POST['installments'] ?? 1));
          $first_due = trim($_POST['first_due'] ?? '');
          $interval = trim($_POST['interval'] ?? 'monthly');
          if ($installments < 2) { throw new Exception('Informe 2 ou mais parcelas.'); }
          if ($total_amount <= 0 || !$first_due) { throw new Exception('Preencha valor total e primeiro vencimento.'); }

          $base = floor(($total_amount / $installments) * 100) / 100.0;
          $amounts = array_fill(0, $installments, $base);
          $remainder = round($total_amount - array_sum($amounts), 2);
          if ($remainder !== 0.0) { $amounts[$installments - 1] = round($amounts[$installments - 1] + $remainder, 2); }

          try { $dt = new DateTime($first_due); } catch (Throwable $e) { throw new Exception('Primeiro vencimento inválido.'); }

          $stmt = $pdo->prepare('INSERT INTO payables (supplier_id, account_type_id, account_id, description, category, due_date, amount, status, notes) VALUES (?,?,?,?,?,?,?,"open",?)');
          for ($i = 0; $i < $installments; $i++) {
            $due = clone $dt;
            if ($i > 0) { if ($interval === 'weekly') { $due->modify('+'.(7*$i).' day'); } else { $due->modify('+'.$i.' month'); } }
            $descParc = $description . ' (Parcela ' . ($i+1) . '/' . $installments . ')';
            $stmt->execute([$supplier_id ?: null, $account_type_id ?: null, $account_id, $descParc, $category ?: null, $due->format('Y-m-d'), $amounts[$i], $notes ?: null]);
          }
          $infoMsg = 'Parcelamento lançado: ' . $installments . ' parcelas.';
        }
      } else {
        // Lançamento único
        $amount = (float)($_POST['amount'] ?? 0);
        $due_date = trim($_POST['due_date'] ?? '');
        if ($amount <= 0 || !$due_date) { throw new Exception('Preencha valor e vencimento.'); }
        $stmt = $pdo->prepare('INSERT INTO payables (supplier_id, account_type_id, account_id, description, category, due_date, amount, status, notes) VALUES (?,?,?,?,?,?,?,"open",?)');
        $stmt->execute([$supplier_id ?: null, $account_type_id ?: null, $account_id, $description, $category ?: null, $due_date, $amount, $notes ?: null]);
        $infoMsg = 'Conta a pagar lançada.';
      }
    } elseif ($action === 'settle') {
      $id = (int)($_POST['id'] ?? 0);
      $payment_method = trim($_POST['payment_method'] ?? '');
      $settle_account_id = (int)($_POST['settle_account_id'] ?? 0);
      if ($id <= 0) { throw new Exception('ID inválido.'); }
      if ($settle_account_id <= 0) { throw new Exception('Selecione a conta utilizada na baixa.'); }
      $stmt = $pdo->prepare('UPDATE payables SET status="paid", paid_at=NOW(), payment_method=? WHERE id=?');
      $stmt->execute([$payment_method ?: null, $id]);
      // Registrar saída no razão da conta vinculada
      try {
        $ps = $pdo->prepare('SELECT description, amount FROM payables WHERE id=?');
        $ps->execute([$id]);
        $pay = $ps->fetch();
        if ($pay) {
          $desc = 'Pagamento: ' . (string)($pay['description'] ?? '');
          $amt = (float)($pay['amount'] ?? 0);
          $accId = (int)$settle_account_id;
          $ls = $pdo->prepare('INSERT INTO account_ledgers (account_id, movement_type, amount, description, related_table, related_id) VALUES (?,?,?,?,?,?)');
          $ls->execute([$accId, 'debit', $amt, $desc ?: null, 'payables', $id]);
        }
      } catch (Throwable $e) { /* não interromper baixa em caso de erro no razão */ }
      $infoMsg = 'Pagamento baixado com sucesso.';
    } elseif ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) { throw new Exception('ID inválido.'); }
      $pdo->prepare('DELETE FROM payables WHERE id=?')->execute([$id]);
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
    $chk->execute([$dbName, 'payables', 'account_type_id']);
    $hasAcctTypeCol = ((int)$chk->fetchColumn() > 0);
  } catch (Throwable $e) {}
}

$sql = 'SELECT p.*, s.name AS supplier_name' . ($hasAcctTypeCol ? ', at.name AS account_type_name, at.classification AS account_type_classification ' : ' ') .
       'FROM payables p LEFT JOIN suppliers s ON s.id = p.supplier_id ' . ($hasAcctTypeCol ? 'LEFT JOIN account_types at ON at.id = p.account_type_id ' : '') . 'WHERE 1=1';
$params = [];
if ($q) { $sql .= ' AND (p.description LIKE ? OR s.name LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($status) { $sql .= ' AND p.status = ?'; $params[] = $status; }
if ($from) { $sql .= ' AND p.due_date >= ?'; $params[] = $from; }
if ($to) { $sql .= ' AND p.due_date <= ?'; $params[] = $to; }
$account_type_id_filter = (int)($_GET['account_type_id'] ?? 0);
if ($hasAcctTypeCol && $account_type_id_filter) { $sql .= ' AND p.account_type_id = ?'; $params[] = $account_type_id_filter; }
if ($hasAcctTypeCol && ($classification === 'receita' || $classification === 'despesa')) { $sql .= ' AND at.classification = ?'; $params[] = $classification; }
$sql .= ' ORDER BY p.due_date ASC';

$rows = [];
try { $stmt = $pdo->prepare($sql); $stmt->execute($params); $rows = $stmt->fetchAll(); } catch (Throwable $e) { $error = 'Erro ao consultar: ' . $e->getMessage(); }

// Load suppliers for selects
$suppliers = [];
try { $suppliers = $pdo->query('SELECT id, name FROM suppliers ORDER BY name')->fetchAll(); } catch (Throwable $e) {}

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
    <title><?php echo page_title('Contas a Pagar'); ?></title>
  <?php include __DIR__ . '/partials/header.php'; ?>
</head>
<body>
<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Contas a Pagar</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreate">Novo Lançamento</button>
  </div>

  <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
  <?php if ($infoMsg): ?><div class="alert alert-success"><?php echo htmlspecialchars($infoMsg); ?></div><?php endif; ?>

  <form class="card p-3 mb-3 filters-compact filters-xs" method="get">
    <div class="row g-2">
      <div class="col-md-4"><label class="form-label">Busca</label><input name="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="Descrição ou fornecedor"></div>
      <div class="col-md-2"><label class="form-label">Status</label>
        <select class="form-select" name="status">
          <option value="">Todos</option>
          <option value="open" <?php echo $status==='open'?'selected':''; ?>>Em aberto</option>
          <option value="paid" <?php echo $status==='paid'?'selected':''; ?>>Pago</option>
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
  <div class="card p-3 summary-sticky sticky-xs summary-compact summary-xs mb-3">
    <?php
      $totOpen = $pdo->query("SELECT COALESCE(SUM(amount),0) AS t FROM payables WHERE status='open'")->fetch()['t'] ?? 0;
      $totPaid = $pdo->query("SELECT COALESCE(SUM(amount),0) AS t FROM payables WHERE status='paid'")->fetch()['t'] ?? 0;
      $totAll = (float)$totOpen + (float)$totPaid;
      $pctPaid = $totAll > 0 ? ($totPaid / $totAll) : 0;
      $pctOpen = $totAll > 0 ? ($totOpen / $totAll) : 0;
      $cntOpen = (int)($pdo->query("SELECT COUNT(*) FROM payables WHERE status='open'")->fetchColumn() ?: 0);
      $cntPaid = (int)($pdo->query("SELECT COUNT(*) FROM payables WHERE status='paid'")->fetchColumn() ?: 0);
      $cntCancelled = (int)($pdo->query("SELECT COUNT(*) FROM payables WHERE status='cancelled'")->fetchColumn() ?: 0);
      $cntAll = $cntOpen + $cntPaid + $cntCancelled;
    ?>
    <div class="status-chips">
      <a href="/contas_pagar.php" class="status-chip <?php echo $status? '': 'active'; ?>">Todas <span class="chip-count" title="Quantidade total de lançamentos"><?php echo $cntAll; ?></span></a>
      <a href="/contas_pagar.php?status=open" class="status-chip <?php echo $status==='open'? 'active': ''; ?>">Em aberto <span class="chip-count" title="Lançamentos ainda não pagos"><?php echo $cntOpen; ?></span></a>
      <a href="/contas_pagar.php?status=paid" class="status-chip <?php echo $status==='paid'? 'active': ''; ?>">Pagas <span class="chip-count" title="Lançamentos baixados/pagos"><?php echo $cntPaid; ?></span></a>
      <a href="/contas_pagar.php?status=cancelled" class="status-chip <?php echo $status==='cancelled'? 'active': ''; ?>">Canceladas <span class="chip-count" title="Lançamentos cancelados"><?php echo $cntCancelled; ?></span></a>
    </div>
    <div class="ap-summary">
      <div class="ap-tile ap-open" title="Soma de contas em aberto">
        <div class="ap-label">Em aberto</div>
        <div class="ap-value">R$ <?php echo number_format((float)$totOpen, 2, ',', '.'); ?></div>
      </div>
      <div class="ap-tile ap-paid" title="Soma de contas pagas">
        <div class="ap-label">Pagas</div>
        <div class="ap-value">R$ <?php echo number_format((float)$totPaid, 2, ',', '.'); ?></div>
      </div>
      <div>
        <div class="ap-progress" title="Composição entre pagas e em aberto">
          <div class="ap-progress-bar">
            <div class="ap-progress-paid" style="width: <?php echo round($pctPaid*100, 1); ?>%" title="Pagas: <?php echo round($pctPaid*100, 1); ?>%"></div>
            <div class="ap-progress-open" style="width: <?php echo round($pctOpen*100, 1); ?>%" title="Em aberto: <?php echo round($pctOpen*100, 1); ?>%"></div>
          </div>
          <div class="ap-progress-meta">
            <div>Pago: <?php echo round($pctPaid*100, 1); ?>%</div>
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
        <thead><tr><th>#</th><th>Vencimento</th><th>Descrição</th><th>Fornecedor</th><th>Tipo</th><th>Classificação</th><th>Valor</th><th>Status</th><th>Ações</th></tr></thead>
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
            <td><?php echo htmlspecialchars($r['supplier_name'] ?? '—'); ?></td>
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
            <td><span class="badge bg-<?php echo $r['status']==='paid'?'success':($r['status']==='cancelled'?'secondary':'warning'); ?>"><?php echo htmlspecialchars($r['status']); ?></span></td>
            <td class="text-nowrap">
              <?php if ($r['status']==='open'): ?>
              <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalSettle" data-id="<?php echo (int)$r['id']; ?>">Baixa</button>
              <?php endif; ?>
              <form method="post" action="/contas_pagar.php" style="display:inline" onsubmit="return confirm('Excluir lançamento?');">
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
      <form method="post" action="/contas_pagar.php">
        <input type="hidden" name="action" value="create">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Descrição *</label><input class="form-control" name="description" required></div>
            <div class="col-md-3"><label class="form-label">Valor *</label><input type="number" step="0.01" min="0" class="form-control" name="amount" id="single-amount" required></div>
            <div class="col-md-3"><label class="form-label">Vencimento *</label><input type="date" class="form-control" name="due_date" id="single-due" required></div>
            <div class="col-md-6"><label class="form-label">Fornecedor</label>
              <select name="supplier_id" class="form-select">
                <option value="">—</option>
                <?php foreach($suppliers as $s){ echo '<option value="'.(int)$s['id'].'">'.htmlspecialchars($s['name']).'</option>'; } ?>
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
            <!-- Parcelamento (opcional) -->
            <div class="col-12">
              <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="parcel_generate" name="parcel_generate" value="1">
                <label class="form-check-label" for="parcel_generate">Lançar em parcelas</label>
              </div>
            </div>
            <div id="parcel-fields" class="row g-2 mt-2 d-none">
              <div class="col-md-3"><label class="form-label">Valor Total *</label><input type="number" step="0.01" min="0" class="form-control" name="total_amount" id="total-amount"></div>
              <div class="col-md-2"><label class="form-label">Parcelas *</label><input type="number" min="2" step="1" class="form-control" name="installments" id="installments" placeholder="2"></div>
              <div class="col-md-3"><label class="form-label">Vencimento *</label><input type="date" class="form-control" name="first_due" id="first-due"></div>
              <div class="col-md-2"><label class="form-label">Intervalo</label>
                <select class="form-select" name="interval" id="interval">
                  <option value="monthly" selected>Mensal</option>
                  <option value="weekly">Semanal</option>
                </select>
              </div>
              <div class="col-md-2 d-flex align-items-end"><button type="button" class="btn btn-outline-light w-100" id="btn-generate">Gerar Parcelas</button></div>
            </div>
            <div id="parcel-preview" class="mt-3 d-none">
              <input type="hidden" name="parcel_count" id="parcel-count" value="0">
              <table class="table table-sm table-hover align-middle table-compact table-xs">
                <thead><tr><th>Parcela</th><th>Emissão</th><th>Vencimento</th><th>Valor</th></tr></thead>
                <tbody id="parcel-preview-body"></tbody>
              </table>
            </div>
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
      <div class="modal-header"><h5 class="modal-title">Baixa de Pagamento</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <form method="post" action="/contas_pagar.php">
        <input type="hidden" name="action" value="settle">
        <input type="hidden" name="id" id="settle-id">
        <div class="modal-body">
          <label class="form-label">Forma de pagamento</label>
          <select class="form-select" name="payment_method">
            <option value="cash">Dinheiro</option>
            <option value="bank_transfer">Transferência</option>
            <option value="pix">PIX</option>
            <option value="other">Outro</option>
          </select>
          <div class="mt-3">
            <label class="form-label">Conta utilizada na baixa *</label>
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

// ---- Parcelas (Preview e validação) ----
(function(){
  const chk = document.getElementById('parcel_generate');
  const fields = document.getElementById('parcel-fields');
  const preview = document.getElementById('parcel-preview');
  const tbody = document.getElementById('parcel-preview-body');
  const totalInput = document.getElementById('total-amount');
  const instInput = document.getElementById('installments');
  const firstDueInput = document.getElementById('first-due');
  const intervalSel = document.getElementById('interval');
  const btnGenerate = document.getElementById('btn-generate');
  const singleAmount = document.getElementById('single-amount');
  const singleDue = document.getElementById('single-due');
  const parcelCountInput = document.getElementById('parcel-count');

  function createHiddenInput(name, value){
    const input = document.createElement('input');
    input.type = 'hidden'; input.name = name; input.value = value;
    return input;
  }

  function formatBR(date){
    const d = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    const dd = String(d.getDate()).padStart(2,'0');
    const mm = String(d.getMonth()+1).padStart(2,'0');
    const yyyy = d.getFullYear();
    return `${dd}/${mm}/${yyyy}`;
  }
  function addMonths(date, n){ const d = new Date(date); d.setMonth(d.getMonth()+n); return d; }
  function addDays(date, n){ const d = new Date(date); d.setDate(d.getDate()+n); return d; }

  function generate(){
    const total = parseFloat(totalInput.value || '0');
    const inst = parseInt(instInput.value || '0', 10);
    const fd = firstDueInput.value;
    const interval = intervalSel.value || 'monthly';
    tbody.innerHTML = '';
    if (!(total>0) || !(inst>=2) || !fd) { preview.classList.add('d-none'); return; }

    // cálculo igual ao backend: base + ajuste no último
    const base = Math.floor((total/inst)*100)/100;
    const amounts = Array(inst).fill(base);
    let remainder = +(total - amounts.reduce((a,b)=>a+b,0)).toFixed(2);
    if (remainder !== 0) { amounts[inst-1] = +(amounts[inst-1] + remainder).toFixed(2); }

    const first = new Date(fd+'T00:00:00');
    const emission = new Date();
    // clear any previously added hidden inputs
    // ensure we add new hidden array fields alongside table rows
    const form = document.querySelector('#modalCreate form');
    // remove previous parcel_* hidden inputs
    form?.querySelectorAll('input[name^="parcel_due[]"], input[name^="parcel_amount[]"]').forEach(el=>el.remove());

    for (let i=0;i<inst;i++){
      let due = new Date(first);
      if (i>0){ due = (interval==='weekly') ? addDays(first, 7*i) : addMonths(first, i); }
      const tr = document.createElement('tr');
      const dueStr = `${due.getFullYear()}-${String(due.getMonth()+1).padStart(2,'0')}-${String(due.getDate()).padStart(2,'0')}`;
      const amtStr = amounts[i].toFixed(2);
      tr.innerHTML = `
        <td>${i+1}</td>
        <td>${formatBR(emission)}</td>
        <td><input type="date" class="form-control form-control-sm" value="${dueStr}" data-index="${i}" name="parcel_due[]"></td>
        <td>
          <div class="input-group input-group-sm">
            <span class="input-group-text">R$</span>
            <input type="number" step="0.01" min="0" class="form-control form-control-sm" value="${amtStr}" data-index="${i}" name="parcel_amount[]">
          </div>
        </td>`;
      tbody.appendChild(tr);
    }
    if (parcelCountInput) parcelCountInput.value = inst;
    preview.classList.remove('d-none');
  }

  function toggleRequired(enableParcels){
    // Quando parcelado: desabilitar obrigatoriedade do valor único e vencimento simples
    if (singleAmount) singleAmount.required = !enableParcels;
    if (singleDue) singleDue.required = !enableParcels;
    if (totalInput) totalInput.required = enableParcels;
    if (instInput) instInput.required = enableParcels;
    if (firstDueInput) firstDueInput.required = enableParcels;
  }

  function toggleUI(){
    const on = !!(chk && chk.checked);
    if (fields) fields.classList.toggle('d-none', !on);
    if (!on && preview) { preview.classList.add('d-none'); }
    toggleRequired(on);
  }

  chk?.addEventListener('change', toggleUI);
  btnGenerate?.addEventListener('click', generate);
  totalInput?.addEventListener('input', ()=>{ if (!preview.classList.contains('d-none')) generate(); });
  instInput?.addEventListener('input', ()=>{ if (!preview.classList.contains('d-none')) generate(); });
  firstDueInput?.addEventListener('change', ()=>{ if (!preview.classList.contains('d-none')) generate(); });
  intervalSel?.addEventListener('change', ()=>{ if (!preview.classList.contains('d-none')) generate(); });

  // Inicialização ao abrir modal
  document.getElementById('modalCreate')?.addEventListener('shown.bs.modal', function(){ toggleUI(); });
})();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>