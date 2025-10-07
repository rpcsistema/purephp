<?php
require __DIR__ . '/bootstrap.php';

if (!Auth::check()) {
    redirect('/login.php');
}

$pdo = Database::pdo();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('/clientes.php');
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
            $stmt = $pdo->prepare('UPDATE clients SET name = ?, email = ?, phone = ?, company = ? WHERE id = ?');
            $stmt->execute([$name, $email, $phone ?: null, $company ?: null, $id]);
            redirect('/clientes.php');
        } catch (Throwable $e) {
            $error = 'Erro ao atualizar: ' . $e->getMessage();
        }
    }
}

$stmt = $pdo->prepare('SELECT id, name, email, phone, company FROM clients WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$c = $stmt->fetch();
if (!$c) {
    redirect('/clientes.php');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo page_title('Editar Cliente'); ?></title>
    <?php include __DIR__ . '/partials/header.php'; ?>
</head>
<body>
<div class="container mt-4">
    <h3>Editar Cliente #<?php echo (int)$c['id']; ?></h3>
    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post" action="/clientes_edit.php?id=<?php echo (int)$c['id']; ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nome</label>
                <input type="text" class="form-control" name="name" required value="<?php echo htmlspecialchars($c['name']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">E-mail</label>
                <input type="email" class="form-control" name="email" required value="<?php echo htmlspecialchars($c['email']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Telefone</label>
                <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($c['phone'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Empresa</label>
                <input type="text" class="form-control" name="company" value="<?php echo htmlspecialchars($c['company'] ?? ''); ?>">
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