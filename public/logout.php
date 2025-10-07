<?php
require __DIR__ . '/bootstrap.php';

// Encerrar a sessão e redirecionar para o login
Auth::logout();
redirect('/login.php');