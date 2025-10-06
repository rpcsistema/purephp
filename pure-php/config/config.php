<?php
return [
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'name' => getenv('DB_NAME') ?: 'saaswl',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => 'RPC-SISTEMA',
        'tagline' => 'Sua empresa sob seu controle total, com todas as informações e ferramentas necessárias para gerenciar e tomar decisões estratégicas de forma eficiente e segura.',
    ],
];