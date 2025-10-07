<?php
require __DIR__ . '/bootstrap.php';

if (!Auth::check()) {
    redirect('/login.php');
}

$pdo = Database::pdo();
$user = Auth::user();
$successMsg = null;
$errorMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    // Validar campos básicos
    if ($name === '' || $email === '') {
        $errorMsg = 'Nome e e-mail são obrigatórios.';
    } else {
        try {
            // Verificar conflito de e-mail com outros usuários
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
            $stmt->execute([$email, $user['id']]);
            $existsEmail = $stmt->fetchColumn();
            if ($existsEmail) {
                $errorMsg = 'E-mail já está em uso por outro usuário.';
            } else {
                // Se for troca de senha, validar senha atual e confirmação
                $doChangePassword = ($newPassword !== '' || $confirmPassword !== '');
                if ($doChangePassword) {
                    if ($newPassword !== $confirmPassword) {
                        $errorMsg = 'Nova senha e confirmação não coincidem.';
                    } else {
                        // Buscar hash atual para validar senha corrente
                        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
                        $stmt->execute([$user['id']]);
                        $hash = (string)($stmt->fetchColumn() ?: '');
                        if (!$hash || !password_verify($currentPassword, $hash)) {
                            $errorMsg = 'Senha atual incorreta.';
                        }
                    }
                }

                if (!$errorMsg) {
                    if ($doChangePassword) {
                        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
                        $stmt->execute([$name, $email, $newHash, $user['id']]);
                    } else {
                        $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
                        $stmt->execute([$name, $email, $user['id']]);
                    }

                    // Atualizar sessão
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;

                    $successMsg = 'Perfil atualizado com sucesso!';
                    // Atualizar $user para refletir mudanças
                    $user['name'] = $name;
                    $user['email'] = $email;
                }
            }
        } catch (Throwable $e) {
            $errorMsg = 'Erro ao atualizar perfil. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo page_title('Perfil'); ?></title>
    <?php include __DIR__ . '/partials/header.php'; ?>
</head>
<body>
<div class="container mt-4" style="max-width: 680px;">
    <h1 class="h4 mb-3">Editar Perfil</h1>
    <?php if ($successMsg): ?>
        <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($successMsg); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($errorMsg); ?></div>
    <?php endif; ?>

    <form method="post" action="/profile.php" novalidate>
        <div class="mb-3">
            <label for="name" class="form-label">Nome</label>
            <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($user['name']); ?>">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">E-mail</label>
            <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>">
            <div class="form-text">Seu e-mail deve ser único.</div>
        </div>
        <hr>
        <div class="mb-3">
            <label for="current_password" class="form-label">Senha atual</label>
            <input type="password" class="form-control" id="current_password" name="current_password" placeholder="Informe para trocar a senha">
        </div>
        <div class="mb-3">
            <label for="new_password" class="form-label">Nova senha</label>
            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Opcional">
        </div>
        <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirmar nova senha</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Opcional">
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">Salvar alterações</button>
            <a href="/dashboard.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>