<?php

return [
    'binary_path' => base_path('tools/gallery-dl'.(PHP_OS_FAMILY === 'Windows' ? '.exe' : '.bin')),

    'binary_download_url' => env(
        'GALLERY_DL_BINARY_DOWNLOAD_URL',
        PHP_OS_FAMILY === 'Windows'
            ? 'https://github.com/mikf/gallery-dl/releases/latest/download/gallery-dl.exe'
            : 'https://github.com/mikf/gallery-dl/releases/latest/download/gallery-dl.bin'
    ),

    'timeout' => (int) env('GALLERY_DL_TIMEOUT', 300),
];
