<?php
require __DIR__ . '/bootstrap.php';

if (!Auth::check()) {
    redirect('/login.php');
}

$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo page_title('Clientes'); ?></title>
    <?php include __DIR__ . '/partials/header.php'; ?>
</head>
<body>

<?php
// Buscar clientes
$pdo = Database::pdo();
$clients = [];
$infoMsg = null;
try {
    $stmt = $pdo->query('SELECT id, name, email, phone, company FROM clients ORDER BY id DESC');
    $clients = $stmt->fetchAll();
} catch (Throwable $e) {
    // Se a tabela não existir, cria automaticamente via schema.sql e tenta novamente
    $isMissingTable = ($e instanceof \PDOException) && ($e->getCode() === '42S02' || str_contains($e->getMessage(), 'Base table or view not found'));
    if ($isMissingTable) {
        try {
            $pdo->exec(file_get_contents(__DIR__ . '/../sql/schema.sql'));
            $stmt = $pdo->query('SELECT id, name, email, phone, company FROM clients ORDER BY id DESC');
            $clients = $stmt->fetchAll();
            $infoMsg = 'Tabela de clientes criada automaticamente. Se precisar de dados, execute o seed em /seed_user.php.';
        } catch (Throwable $e2) {
            throw $e2;
        }
    } else {
        throw $e;
    }
}
?>
<div class="container mt-4">
    <?php if ($infoMsg): ?>
        <div class="alert alert-warning" role="alert"><?php echo htmlspecialchars($infoMsg); ?></div>
    <?php endif; ?>
    <div class="d-flex align-items-center justify-content-between">
        <h3 class="mb-0">Clientes</h3>
        <div>
            <a class="btn btn-primary" href="/clientes_create.php">Novo Cliente</a>
            <a class="btn btn-secondary" href="/dashboard.php">Voltar</a>
        </div>
    </div>

    <div class="card mt-3">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Telefone</th>
                        <th>Empresa</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $c): ?>
                        <tr>
                            <td><?php echo (int)$c['id']; ?></td>
                            <td><?php echo htmlspecialchars($c['name']); ?></td>
                            <td><?php echo htmlspecialchars($c['email']); ?></td>
                            <td><?php echo htmlspecialchars($c['phone'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($c['company'] ?? ''); ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="/clientes_edit.php?id=<?php echo (int)$c['id']; ?>">Editar</a>
                                <form method="post" action="/clientes_delete.php" style="display:inline" onsubmit="return confirm('Excluir este cliente?');">
                                    <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (empty($clients)): ?>
        <div class="alert alert-info mt-3">Nenhum cliente cadastrado.</div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>