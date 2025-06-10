<?php

return [
    'paths' => [
        resource_path('views'),
    ],

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        $_SERVER['HOME'].'/.breakdown/views'
    ),
];
