<?php
// Simple connection check using the app's config loader
header('Content-Type: text/plain; charset=utf-8');

// Load env
require __DIR__ . '/bootstrap.php';

// Load config
$cfg = require __DIR__ . '/../config/config.php';

echo "Reading .env and config...\n";
echo "DB_HOST=" . $cfg['db']['host'] . "\n";
echo "DB_PORT=" . $cfg['db']['port'] . "\n";
echo "DB_NAME=" . $cfg['db']['name'] . "\n";
echo "DB_USER=" . $cfg['db']['user'] . "\n";

try {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $cfg['db']['host'], $cfg['db']['port'], $cfg['db']['name'], $cfg['db']['charset']);
    $pdo = new PDO($dsn, $cfg['db']['user'], $cfg['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $row = $pdo->query('SELECT VERSION() AS v')->fetch();
    echo "Connected successfully. MySQL version: " . ($row['v'] ?? 'unknown') . "\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Connection failed: " . $e->getMessage() . "\n";
}