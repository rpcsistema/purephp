<?php
require __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = Database::pdo();
// Garantir schema básico disponível
try { $pdo->exec(@file_get_contents(__DIR__ . '/../../sql/schema.sql')); } catch (Throwable $e) {}

$result = [
    'labels' => [],
    'totals' => [],
    'sum_total' => 0,
    'last_updated' => date('c'),
];

try {
    // Agregação apenas de contas pagas (somente baixas)
    $sql = "
      SELECT COALESCE(at.name, 'Sem Tipo') AS type_name,
             SUM(p.amount) AS total
      FROM payables p
      LEFT JOIN account_types at ON at.id = p.account_type_id
      WHERE p.status = 'paid'
      GROUP BY type_name
      ORDER BY total DESC
    ";
    $rows = $pdo->query($sql)->fetchAll();

    foreach ($rows as $r) {
        $type = (string)($r['type_name'] ?? 'Sem Tipo');
        $total = (float)($r['total'] ?? 0);

        $result['labels'][] = $type;
        $result['totals'][] = $total;
        $result['sum_total'] += $total;
    }
    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'query_failed', 'message' => $e->getMessage()]);
}
// Fim