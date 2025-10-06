<?php
require __DIR__ . '/bootstrap.php';

if (!Auth::check()) {
    redirect('/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/clientes.php');
}

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    try {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('DELETE FROM clients WHERE id = ?');
        $stmt->execute([$id]);
    } catch (Throwable $e) {
        // Poderia logar erro; manter simples aqui
    }
}

redirect('/clientes.php');