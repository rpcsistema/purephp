<?php
require __DIR__ . '/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    // Criar database se necessário
    $cfg = require __DIR__ . '/../config/config.php';
    $dsnNoDb = sprintf('mysql:host=%s;charset=%s', $cfg['db']['host'], $cfg['db']['charset']);
    $pdoNoDb = new PDO($dsnNoDb, $cfg['db']['user'], $cfg['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdoNoDb->exec("CREATE DATABASE IF NOT EXISTS `{$cfg['db']['name']}` CHARACTER SET {$cfg['db']['charset']} COLLATE utf8mb4_unicode_ci");

    // Conectar ao DB principal
    $pdo = Database::pdo();
    // Criar tabela se não existir
    $pdo->exec(file_get_contents(__DIR__ . '/../sql/schema.sql'));

    // Criar usuário admin se não existir
    $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
    $stmt->execute(['admin@example.com']);
    $exists = (bool) $stmt->fetchColumn();

    if (!$exists) {
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        $ins = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
        $ins->execute(['Admin', 'admin@example.com', $hash]);
        echo "Usuário admin criado: admin@example.com / admin123\n";
    } else {
        echo "Usuário admin já existe.\n";
    }

    // Popular clientes de exemplo se tabela estiver vazia
    try {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn();
        if ($count === 0) {
            $insC = $pdo->prepare('INSERT INTO clients (name, email, phone, company) VALUES (?, ?, ?, ?)');
            $insC->execute(['Cliente A', 'clienteA@example.com', '+55 11 99999-0001', 'Empresa A']);
            $insC->execute(['Cliente B', 'clienteB@example.com', '+55 21 99999-0002', 'Empresa B']);
            $insC->execute(['Cliente C', 'clienteC@example.com', '+55 31 99999-0003', 'Empresa C']);
            echo "Clientes de exemplo criados.\n";
        } else {
            echo "Tabela de clientes já possui registros.\n";
        }
    } catch (Throwable $e) {
        echo "Aviso ao popular clientes: " . $e->getMessage() . "\n";
    }

    echo "OK.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Erro: ' . $e->getMessage();
}