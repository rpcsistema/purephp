<?php
require __DIR__ . '/bootstrap.php';

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if (!$email || !$password) {
        $error = 'Informe e-mail e senha.';
    } else {
        try {
            if (Auth::attempt($email, $password)) {
                redirect('/dashboard.php');
            } else {
                $error = 'Credenciais inválidas.';
            }
        } catch (Throwable $e) {
            $error = 'Erro ao autenticar: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo page_title('Login'); ?></title>
    <!-- Favicon / Logo na aba -->
    <link rel="icon" href="/assets/logo-rpc.svg" type="image/svg+xml">
    <link rel="shortcut icon" href="/assets/logo-rpc.svg" type="image/svg+xml">
    <meta name="theme-color" content="#2b8f3a">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/theme.css" rel="stylesheet">
    <style>
        .login-card { max-width: 420px; margin: 10vh auto; }
    </style>
    </head>
<body>
    <div class="container">
        <div class="card shadow-sm login-card">
            <div class="card-header" style="background: linear-gradient(90deg, #2b8f3a, #6bd47f); color: white;">
                <div class="d-flex align-items-center gap-2">
                    <img src="/assets/logo.svg" alt="Logo" width="24" height="24">
                    <h5 class="mb-0">Entrar</h5>
                </div>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="post" action="/login.php" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Senha</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Entrar</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-muted">
                <small>Use <code>admin@example.com</code> / <code>admin123</code> após executar o seed.</small>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>