<?php
require __DIR__ . '/bootstrap.php';
// Redireciona conforme autenticação: se logado, vai para o dashboard; caso contrário, para login
if (Auth::check()) {
    redirect('/dashboard.php');
}
redirect('/login.php');