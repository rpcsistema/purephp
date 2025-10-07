<?php
require __DIR__ . '/bootstrap.php';

if (!Auth::check()) {
    redirect('/login.php');
}

$user = Auth::user();
// Preparação de dados para painéis e gráficos
$pdo = Database::pdo();
// Utilitário de moeda BRL
function currencyBRL($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
// Formatação com sinal antes de R$
function currencyBRLWithSign($v) {
    $neg = ((float)$v) < 0; $abs = abs((float)$v);
    $s = 'R$ ' . number_format($abs, 2, ',', '.');
    return $neg ? ('-' . $s) : $s;
}

// Datas de referência
$today = new DateTimeImmutable('today');
$firstDayMonth = $today->modify('first day of this month');
$lastDayMonth = $today->modify('last day of this month');

// Carregar contas ativas
$accounts = [];
try {
    $stmt = $pdo->query("SELECT id, name, initial_balance, is_active FROM accounts WHERE is_active = 1 ORDER BY name");
    $accounts = $stmt->fetchAll();
} catch (Throwable $e) {}

// Agregados do ledger
$ledgerCredits = [];
$ledgerDebits = [];
try {
    $rows = $pdo->query("SELECT account_id, movement_type, SUM(amount) AS total FROM account_ledgers GROUP BY account_id, movement_type")->fetchAll();
    foreach ($rows as $r) {
        if (($r['movement_type'] ?? '') === 'credit') {
            $ledgerCredits[(int)$r['account_id']] = (float)$r['total'];
        } elseif (($r['movement_type'] ?? '') === 'debit') {
            $ledgerDebits[(int)$r['account_id']] = (float)$r['total'];
        }
    }
} catch (Throwable $e) {}

// Agregados do ledger desconsiderando transferências (não devem impactar projeções)
$ledgerCreditsNoTransfer = [];
$ledgerDebitsNoTransfer = [];
try {
    $rows2 = $pdo->query("SELECT account_id, movement_type, SUM(amount) AS total FROM account_ledgers WHERE (related_table IS NULL OR related_table <> 'transfer') GROUP BY account_id, movement_type")->fetchAll();
    foreach ($rows2 as $r) {
        if (($r['movement_type'] ?? '') === 'credit') {
            $ledgerCreditsNoTransfer[(int)$r['account_id']] = (float)$r['total'];
        } elseif (($r['movement_type'] ?? '') === 'debit') {
            $ledgerDebitsNoTransfer[(int)$r['account_id']] = (float)$r['total'];
        }
    }
} catch (Throwable $e) {}

$accountBalances = [];
$consolidatedBalance = 0.0;
// Saldo consolidado ignorando transferências para uso em projeções
$consolidatedBalanceNoTransfer = 0.0;
foreach ($accounts as $a) {
    $id = (int)$a['id'];
    $initial = (float)($a['initial_balance'] ?? 0);
    $credits = (float)($ledgerCredits[$id] ?? 0);
    $debits = (float)($ledgerDebits[$id] ?? 0);
    $creditsNT = (float)($ledgerCreditsNoTransfer[$id] ?? 0);
    $debitsNT = (float)($ledgerDebitsNoTransfer[$id] ?? 0);
    $balance = $initial + $credits - $debits;
    $accountBalances[$id] = [
        'id' => $id,
        'name' => (string)$a['name'],
        'balance' => $balance,
    ];
    $consolidatedBalance += $balance;
    $consolidatedBalanceNoTransfer += ($initial + $creditsNT - $debitsNT);
}

// Projeções do mês atual
$monthReceipts = 0.0;
$monthPayments = 0.0;
// Recebimentos previstos (vencimento no mês)
try {
    $stmt = $pdo->prepare('SELECT SUM(amount) FROM receivables WHERE status = "open" AND due_date BETWEEN ? AND ?');
    $stmt->execute([$firstDayMonth->format('Y-m-d'), $lastDayMonth->format('Y-m-d')]);
    $monthReceiptsOpenDue = (float)($stmt->fetchColumn() ?: 0);
} catch (Throwable $e) { $monthReceiptsOpenDue = 0.0; }
// Recebimentos realizados (recebidos no mês)
try {
    $stmt = $pdo->prepare('SELECT SUM(amount) FROM receivables WHERE status = "received" AND DATE(received_at) BETWEEN ? AND ?');
    $stmt->execute([$firstDayMonth->format('Y-m-d'), $lastDayMonth->format('Y-m-d')]);
    $monthReceiptsRealized = (float)($stmt->fetchColumn() ?: 0);
} catch (Throwable $e) { $monthReceiptsRealized = 0.0; }
$monthReceipts = $monthReceiptsOpenDue + $monthReceiptsRealized;

// Pagamentos previstos (vencimento no mês)
try {
    $stmt = $pdo->prepare('SELECT SUM(amount) FROM payables WHERE status = "open" AND due_date BETWEEN ? AND ?');
    $stmt->execute([$firstDayMonth->format('Y-m-d'), $lastDayMonth->format('Y-m-d')]);
    $monthPaymentsOpenDue = (float)($stmt->fetchColumn() ?: 0);
} catch (Throwable $e) { $monthPaymentsOpenDue = 0.0; }
// Pagamentos realizados (pagos no mês)
try {
    $stmt = $pdo->prepare('SELECT SUM(amount) FROM payables WHERE status = "paid" AND DATE(paid_at) BETWEEN ? AND ?');
    $stmt->execute([$firstDayMonth->format('Y-m-d'), $lastDayMonth->format('Y-m-d')]);
    $monthPaymentsRealized = (float)($stmt->fetchColumn() ?: 0);
} catch (Throwable $e) { $monthPaymentsRealized = 0.0; }
$monthPayments = $monthPaymentsOpenDue + $monthPaymentsRealized;
$monthNet = $monthReceipts - $monthPayments;

// Percentual da barra de pagamentos (comparado ao maior entre pagamentos/recebimentos)
$barMax = max(abs($monthPayments), abs($monthReceipts), 1);
$monthPaymentsPercent = (int)round((abs($monthPayments) / $barMax) * 100);

function computeProjection(PDO $pdo, DateTimeImmutable $start, DateTimeImmutable $end, float $startingBalance): array {
    $receivers = [];
    $payers = [];
    try {
        $stmt = $pdo->prepare('SELECT due_date, SUM(amount) AS total FROM receivables WHERE status IN ("open","received") AND due_date BETWEEN ? AND ? GROUP BY due_date ORDER BY due_date');
        $stmt->execute([$start->format('Y-m-d'), $end->format('Y-m-d')]);
        foreach ($stmt->fetchAll() as $r) { $receivers[$r['due_date']] = (float)$r['total']; }
    } catch (Throwable $e) {}
    try {
        $stmt = $pdo->prepare('SELECT due_date, SUM(amount) AS total FROM payables WHERE status IN ("open","paid") AND due_date BETWEEN ? AND ? GROUP BY due_date ORDER BY due_date');
        $stmt->execute([$start->format('Y-m-d'), $end->format('Y-m-d')]);
        foreach ($stmt->fetchAll() as $r) { $payers[$r['due_date']] = (float)$r['total']; }
    } catch (Throwable $e) {}

    $labels = [];
    $saldoSeries = [];
    $receberSeries = [];
    $pagarSeries = [];
    $balance = $startingBalance;
    for ($d = $start; $d <= $end; $d = $d->modify('+1 day')) {
        $key = $d->format('Y-m-d');
        $labels[] = $d->format('d M');
        $receber = (float)($receivers[$key] ?? 0);
        $pagar = (float)($payers[$key] ?? 0);
        $balance += $receber - $pagar;
        $saldoSeries[] = round($balance, 2);
        $receberSeries[] = round($receber, 2);
        $pagarSeries[] = round(-$pagar, 2);
    }
    return [ 'labels' => $labels, 'saldo' => $saldoSeries, 'receber' => $receberSeries, 'pagar' => $pagarSeries ];
}

// Projeções devem ignorar transferências no saldo inicial
$projMonth = computeProjection($pdo, $firstDayMonth, $lastDayMonth, $consolidatedBalanceNoTransfer);
$proj15 = computeProjection($pdo, $today, $today->modify('+15 days'), $consolidatedBalanceNoTransfer);
$proj30 = computeProjection($pdo, $today, $today->modify('+30 days'), $consolidatedBalanceNoTransfer);
$proj60 = computeProjection($pdo, $today, $today->modify('+60 days'), $consolidatedBalanceNoTransfer);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo page_title('Dashboard'); ?></title>
    <?php include __DIR__ . '/partials/header.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
      /* Paleta e contraste focados no dashboard */
      .dashboard-dark { 
        --surface: #1f242d; 
        --surface-2: #161b22; 
        --border: #2b3440; 
        --text: #e6edf3; 
        --muted: #a1a8b3; 
        --accent: #4ea8de; 
        --good: #22c55e; 
        --bad: #ef4444; 
      }
      .dashboard-dark .financial-card.card { 
        background-color: var(--surface); 
        border-color: var(--border); 
        color: var(--text); 
        border-radius: 12px;
        height: 100%;
      }
      .dashboard-dark .financial-card .card-body { padding: 1rem 1.25rem; }
      .dashboard-dark .financial-card:hover { 
        border-color: var(--accent); 
        box-shadow: 0 0 0 1px rgba(78,168,222,0.25), 0 6px 18px rgba(0,0,0,0.28);
        transform: translateY(-1px);
        transition: box-shadow .2s ease, border-color .2s ease, transform .12s ease;
      }
      .dashboard-dark .section-title { color: var(--text); margin-bottom: .5rem; letter-spacing: .01em; }
      .dashboard-dark .text-muted { color: var(--muted) !important; }
      .dashboard-dark .value-pos { color: var(--good); font-weight: 600; }
      .dashboard-dark .value-neg { color: var(--bad); font-weight: 600; }
      .dashboard-dark .btn-group .btn { color: var(--text); border-color: var(--accent); }
      .dashboard-dark .btn-group .btn:hover { background-color: rgba(78,168,222,.12); }
      .dashboard-dark .btn-group .btn.active { background-color: rgba(78,168,222,.22); color: var(--text); border-color: var(--accent); box-shadow: 0 0 0 1px rgba(78,168,222,0.25); }
      /* Ajustes de espaçamento entre seções */
      .dashboard-dark .mt-4 { margin-top: 1.25rem !important; }
      .dashboard-dark .row.gx-3 { --bs-gutter-x: 1rem; }
      .dashboard-dark .accounts-row { align-items: stretch; }
      .dashboard-dark .charts-row { align-items: stretch; }
      /* Gráfico da situação do mês (barra horizontal) */
      .dashboard-dark .month-lines { max-width: 640px; }
      .dashboard-dark .info-i { display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; border-radius:999px; border:1px solid var(--border); color: var(--muted); font-size:12px; margin-left:6px; }
      .dashboard-dark .month-bar { width:100%; height:20px; border-radius:10px; background-color: rgba(239,68,68,0.15); border:1px solid var(--border); overflow:hidden; }
      .dashboard-dark .month-bar-fill { height:100%; background-image: linear-gradient(to right, #ef4444 0%, #ef4444 20%, rgba(239,68,68,0.55) 20%, rgba(239,68,68,0.35) 100%); }
    </style>
</head>
<body>

<div class="container mt-4 dashboard-dark">
    <h1 class="h4 mb-4">Dashboard financeiro</h1>

    <!-- Painéis de saldos por conta -->
    <div class="row g-3 accounts-row gx-3">
        <?php if (!empty($accountBalances)): ?>
            <?php foreach ($accountBalances as $acc): ?>
                <div class="col-md-4">
                    <div class="card shadow-sm financial-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($acc['name']); ?></div>
                                    <div class="text-muted small">Conta ativa</div>
                                </div>
                                <div class="fs-5 <?php echo ($acc['balance'] < 0) ? 'value-neg' : 'value-pos'; ?>">
                                    <?php echo currencyBRL($acc['balance']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-warning">Nenhuma conta ativa encontrada.</div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Saldo consolidado -->
    <div class="mt-4">
        <div class="card financial-card">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div class="fw-semibold section-title">Saldo consolidado</div>
                <div class="fs-5 <?php echo ($consolidatedBalance < 0) ? 'value-neg' : 'value-pos'; ?>">
                    <?php echo currencyBRL($consolidatedBalance); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos lado a lado -->
    <div class="mt-4">
      <div class="row g-3 charts-row gx-3">
        <!-- Coluna: Situação financeira do mês -->
        <div class="col-lg-6">
          <div class="card financial-card h-100">
            <div class="card-body text-center">
              <h2 class="h6 section-title">Situação no mês atual</h2>
              <div class="d-flex justify-content-center">
                <div class="text-start month-lines w-100">
                <div>Projeção de recebimentos (recebidas + em aberto): <strong><?php echo currencyBRL($monthReceipts); ?></strong> <span class="info-i" title="Soma de valores recebidos no mês e previstos até o fim do mês">i</span></div>
                <div class="mt-2">Projeção de pagamentos (pagas + em aberto): <strong class="value-neg"><?php echo currencyBRL($monthPayments); ?></strong> <span class="info-i" title="Soma de valores pagos no mês e previstos até o fim do mês">i</span></div>
                </div>
              </div>
              <div class="mt-3">
                <canvas id="monthChart" height="140"></canvas>
              </div>
              <div class="mt-3">
                <div class="text-muted">Projeção de lucro líquido até o final do mês</div>
                <div class="fs-4 <?php echo ($monthNet < 0) ? 'value-neg' : 'value-pos'; ?>">
                  <?php echo currencyBRLWithSign($monthNet); ?>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- Coluna: Projeção do saldo -->
        <div class="col-lg-6">
          <div class="card financial-card h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 mb-0 section-title">Projeção para os próximos dias</h2>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Intervalos">
                  <button class="btn btn-outline-primary" data-range="month">Mês atual</button>
                  <button class="btn btn-outline-primary" data-range="15">15 dias</button>
                  <button class="btn btn-outline-primary" data-range="30">30 dias</button>
                  <button class="btn btn-outline-primary" data-range="60">60 dias</button>
                </div>
              </div>
              <canvas id="projectionChart" height="140"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>
  <!-- Contas a Pagar por Tipo (apenas pagas) -->
    <div class="mt-4">
      <div class="row g-3 charts-row gx-3">
        <div class="col-12">
          <div class="card financial-card h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
  <h2 class="h6 mb-0 section-title">Contas a Pagar por Tipo (pagas)</h2>
                <div class="text-muted small">Atualiza ao recarregar a página</div>
              </div>
              <div id="payablesTypeWrapper" style="max-width: 560px; margin: 0 auto;">
                <canvas id="payablesTypeChart" height="350"></canvas>
              </div>
              <div class="mt-2 text-muted small" id="payablesTypeSummary">—</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="mt-4">
        <a class="btn btn-secondary" href="/clientes.php">Ir para Clientes</a>
    </div>
</div>

<script>
(() => {
  const datasets = {
    month: <?php echo json_encode($projMonth); ?>,
    15: <?php echo json_encode($proj15); ?>,
    30: <?php echo json_encode($proj30); ?>,
    60: <?php echo json_encode($proj60); ?>
  };

  const ctx = document.getElementById('projectionChart');
  const monthCtx = document.getElementById('monthChart');
  const payablesTypeCtx = document.getElementById('payablesTypeChart');

  const computeGlobalMax = (range) => {
    const d = datasets[range];
    const vals = []
      .concat(d.saldo || [])
      .concat(d.receber || [])
      .concat(d.pagar || [])
      .concat([<?php echo (float)$monthReceipts; ?>, <?php echo (float)$monthPayments; ?>]);
    return Math.max(1, ...vals.map(v => Math.abs(Number(v) || 0)));
  };

  const buildConfig = (range, maxAbs) => {
    const d = datasets[range];
    return {
      type: 'line',
      data: {
        labels: d.labels,
        datasets: [
          { label: 'Saldo', data: d.saldo, borderColor: '#4EA8DE', backgroundColor: 'rgba(78,168,222,0.18)', tension: 0.3, pointRadius: 0, borderWidth: 2 },
          { label: 'Contas a Receber', data: d.receber, borderColor: '#3CCB7F', backgroundColor: 'rgba(60,203,127,0.18)', tension: 0.3, pointRadius: 0, borderWidth: 2 },
          { label: 'Contas a Pagar', data: d.pagar, borderColor: '#EF4444', backgroundColor: 'rgba(239,68,68,0.2)', tension: 0.3, pointRadius: 0, borderWidth: 2 }
        ]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'bottom', labels: { color: '#e6edf3' } },
          tooltip: { mode: 'index', intersect: false }
        },
        scales: {
          x: { ticks: { color: '#a1a8b3' }, grid: { color: 'rgba(255,255,255,0.06)' } },
          y: { min: -maxAbs, max: maxAbs, ticks: { color: '#a1a8b3', callback: (v) => 'R$ ' + Number(v).toLocaleString('pt-BR') }, grid: { color: 'rgba(255,255,255,0.06)' } }
        }
      }
    };
  };

  const buildMonthConfig = (maxAbs) => ({
    type: 'bar',
    data: {
      labels: ['Receber', 'Pagar'],
      datasets: [
        // Receber: Recebidas (realizados)
        { label: 'Receber - Recebidas', stack: 'receber', data: [<?php echo (float)$monthReceiptsRealized; ?>, null], backgroundColor: 'rgba(22,163,74,0.65)', borderColor: '#16a34a', borderWidth: 1 },
        // Receber: Em aberto (previstos)
        { label: 'Receber - Em aberto', stack: 'receber', data: [<?php echo (float)$monthReceiptsOpenDue; ?>, null], backgroundColor: 'rgba(60,203,127,0.35)', borderColor: '#3CCB7F', borderWidth: 1 },
        // Pagar: Pagas (realizados) - valores negativos para orientação à esquerda
        { label: 'Pagar - Pagas', stack: 'pagar', data: [null, -<?php echo (float)$monthPaymentsRealized; ?>], backgroundColor: 'rgba(220,38,38,0.65)', borderColor: '#dc2626', borderWidth: 1 },
        // Pagar: Em aberto (previstos)
        { label: 'Pagar - Em aberto', stack: 'pagar', data: [null, -<?php echo (float)$monthPaymentsOpenDue; ?>], backgroundColor: 'rgba(239,68,68,0.35)', borderColor: '#EF4444', borderWidth: 1 }
      ]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      plugins: {
        legend: { position: 'bottom', labels: { color: '#e6edf3' } },
        tooltip: {
          mode: 'index', intersect: false,
          callbacks: {
            label: (ctx) => {
              const v = ctx.parsed.x;
              const sign = v < 0 ? '-' : '';
              return `${ctx.dataset.label}: ${sign}R$ ${Math.abs(v).toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2})}`;
            }
          }
        }
      },
      scales: {
        x: { stacked: true, min: -maxAbs, max: maxAbs, ticks: { color: '#a1a8b3', callback: (v) => 'R$ ' + Number(v).toLocaleString('pt-BR') }, grid: { color: 'rgba(255,255,255,0.06)' } },
        y: { stacked: true, ticks: { color: '#a1a8b3' }, grid: { display: false } }
      }
    }
  });

  let currentRange = 'month';
  let maxAbs = computeGlobalMax(currentRange);
  let chart = new Chart(ctx, buildConfig(currentRange, maxAbs));
  let monthChart = new Chart(monthCtx, buildMonthConfig(maxAbs));
  // Gráfico: Contas a pagar por tipo (pagas) — Pizza
  let payablesTypeChart;
  // Placeholder inicial para evitar card "vazio" antes do primeiro fetch
  try {
    payablesTypeChart = new Chart(payablesTypeCtx, buildPayablesTypeConfig(['Carregando...'], [0]));
    const el = document.getElementById('payablesTypeSummary');
    if (el) el.textContent = 'Carregando dados...';
  } catch {}

  const buildPayablesTypeConfig = (labels, totals) => ({
    type: 'pie',
    data: {
      labels,
      datasets: [
        {
          label: 'Pagas por Tipo',
          data: totals,
          backgroundColor: [
            'rgba(99, 102, 241, 0.7)', // Indigo
            'rgba(34, 197, 94, 0.7)',  // Green
            'rgba(234, 179, 8, 0.7)',  // Amber
            'rgba(239, 68, 68, 0.7)',  // Red
            'rgba(20, 184, 166, 0.7)', // Teal
            'rgba(168, 85, 247, 0.7)', // Purple
            'rgba(59, 130, 246, 0.7)', // Blue
            'rgba(245, 158, 11, 0.7)', // Orange
            'rgba(156, 163, 175, 0.7)' // Gray
          ],
          borderColor: '#1f2937',
          borderWidth: 1
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom', labels: { color: '#e6edf3' } },
        tooltip: {
          callbacks: {
            label: (ctx) => {
              const v = ctx.parsed;
              return `${ctx.label}: R$ ${Number(v).toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2})}`;
            }
          }
        }
      }
    }
  });

  const updateSummary = (sumTotal, lastUpdated) => {
    const el = document.getElementById('payablesTypeSummary');
    el.textContent = `Total pagas: R$ ${Number(sumTotal).toLocaleString('pt-BR')} · Atualizado: ${new Date(lastUpdated).toLocaleString('pt-BR')}`;
  };

  const fetchPayablesByType = async () => {
    try {
      const res = await fetch('/api/payables_by_type.php', { headers: { 'Accept': 'application/json' } });
      if (!res.ok) throw new Error('Falha ao carregar dados');
      const data = await res.json();
      if (payablesTypeChart) payablesTypeChart.destroy();
      const hasData = Array.isArray(data.labels) && data.labels.length > 0 && (data.sum_total || 0) > 0;
      if (hasData) {
        payablesTypeChart = new Chart(payablesTypeCtx, buildPayablesTypeConfig(data.labels, data.totals));
        updateSummary(data.sum_total, data.last_updated);
      } else {
        payablesTypeChart = new Chart(payablesTypeCtx, buildPayablesTypeConfig(['Sem dados'], [0]));
        updateSummary(0, data.last_updated || Date.now());
      }
    } catch (e) {
      console.error('Erro ao atualizar gráfico de payables:', e);
      try {
        if (payablesTypeChart) payablesTypeChart.destroy();
        payablesTypeChart = new Chart(payablesTypeCtx, buildPayablesTypeConfig(['Erro ao carregar'], [0]));
        const el = document.getElementById('payablesTypeSummary');
        if (el) el.textContent = 'Erro ao carregar dados · tente novamente mais tarde';
      } catch {}
    }
  };

  // Primeira carga (sem atualização periódica)
  fetchPayablesByType();
  // Destacar o botão ativo na carga inicial
  document.querySelectorAll('[data-range]').forEach(b => {
    const isActive = b.getAttribute('data-range') === currentRange;
    b.classList.toggle('active', isActive);
    b.setAttribute('aria-pressed', isActive ? 'true' : 'false');
  });

  document.querySelectorAll('[data-range]').forEach(btn => {
    btn.addEventListener('click', () => {
      const r = btn.getAttribute('data-range');
      currentRange = r; // aceita 'month' e valores numéricos como strings
      maxAbs = computeGlobalMax(currentRange);
      chart.destroy();
      monthChart.destroy();
      chart = new Chart(ctx, buildConfig(currentRange, maxAbs));
      monthChart = new Chart(monthCtx, buildMonthConfig(maxAbs));
      // Persistir seleção e destacar botão ativo
      localStorage.setItem('dashboard_range', currentRange);
      document.querySelectorAll('[data-range]').forEach(b => {
        const isActive = b.getAttribute('data-range') === currentRange;
        b.classList.toggle('active', isActive);
        b.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });
    });
  });
})();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>