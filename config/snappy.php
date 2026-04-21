<?php

return [
    'pdf' => [
        'enabled' => true,
        'binary'  => '"C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltopdf.exe"', // ← Tambah quotes!
        'timeout' => 3600,
        'options' => [
            'enable-local-file-access' => true,
        ],
        'env'     => [],
    ],

    'image' => [
        'enabled' => true,
        'binary'  => '"C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltoimage.exe"',
        'timeout' => 3600,
        'options' => [],
        'env'     => [],
    ],
];
