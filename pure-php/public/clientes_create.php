<?php
require __DIR__ . '/bootstrap.php';

if (!Auth::check()) {
    redirect('/login.php');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $company = trim($_POST['company'] ?? '');

    if (!$name || !$email) {
        $error = 'Nome e e-mail são obrigatórios.';
    } else {
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare('INSERT INTO clients (name, email, phone, company) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $email, $phone ?: null, $company ?: null]);
            redirect('/clientes.php');
        } catch (Throwable $e) {
            $error = 'Erro ao salvar: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo page_title('Novo Cliente'); ?></title>
    <?php include __DIR__ . '/partials/header.php'; ?>
</head>
<body>
<div class="container mt-4">
    <h3>Novo Cliente</h3>
    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post" action="/clientes_create.php">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nome</label>
                <input type="text" class="form-control" name="name" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">E-mail</label>
                <input type="email" class="form-control" name="email" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Telefone</label>
                <input type="text" class="form-control" name="phone">
            </div>
            <div class="col-md-6">
                <label class="form-label">Empresa</label>
                <input type="text" class="form-control" name="company">
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Salvar</button>
            <a class="btn btn-secondary" href="/clientes.php">Cancelar</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>