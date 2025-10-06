<?php

return [
    // Paths onde o Laravel buscará as views (blade)
    'paths' => [resource_path('views')],

    // Diretório onde as views compiladas serão armazenadas
    'compiled' => env(
        'VIEW_COMPILED_PATH',
        realpath(storage_path('framework/views'))
    ),
];