<?php
require_once __DIR__ . '/../bootstrap.php';
$user = Auth::user();
?>
<!-- Favicon / Logo na aba -->
<link rel="icon" href="/assets/logo-rpc.svg" type="image/svg+xml">
<link rel="shortcut icon" href="/assets/logo-rpc.svg" type="image/svg+xml">
<meta name="theme-color" content="#2b8f3a">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="/assets/theme.css" rel="stylesheet">

<nav class="navbar navbar-expand-lg navbar-modern">
  <div class="container-fluid">
    <a class="navbar-brand" href="/dashboard.php" title="<?php echo htmlspecialchars(app_tagline()); ?>">
      <img src="/assets/logo-rpc.svg" alt="RPC-SISTEMA" width="28" height="28">
      <?php echo htmlspecialchars(app_name()); ?>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarMain">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <!-- Cadastro -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navCadastro" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Cadastro
          </a>
          <ul class="dropdown-menu" aria-labelledby="navCadastro">
            <li><a class="dropdown-item" href="/clientes.php">Clientes</a></li>
            <li><a class="dropdown-item" href="/fornecedores.php">Fornecedores</a></li>
          </ul>
        </li>
        <!-- Financeiro -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navFinanceiro" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Financeiro
          </a>
          <ul class="dropdown-menu" aria-labelledby="navFinanceiro">
            <li><a class="dropdown-item" href="/contas_pagar.php">Contas a Pagar</a></li>
            <li><a class="dropdown-item" href="/contas_receber.php">Contas a Receber</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="/contas_financeiras.php">Contas Financeiras</a></li>
            <li><a class="dropdown-item" href="/transferencias.php">Transferências</a></li>
            <li><a class="dropdown-item" href="/tipos_conta.php">Tipos de Conta</a></li>
          </ul>
        </li>
      </ul>
      <div class="d-flex align-items-center gap-3">
        <?php if ($user): ?>
          <div class="dropdown">
            <button class="btn btn-outline-light dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
              Olá, <?php echo htmlspecialchars($user['name']); ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
              <li><a class="dropdown-item" href="/profile.php">Editar perfil</a></li>
              <li><a class="dropdown-item" href="/update.php">Atualizações</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="/logout.php">Sair</a></li>
            </ul>
          </div>
        <?php else: ?>
          <a class="btn btn-primary" href="/login.php">Entrar</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>