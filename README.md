# Laravel Gallery

A Laravel wrapper around `gallery-dl` for profile scans.

## Installation

```bash
composer require nexxai/laravel-gallery
php artisan vendor:publish --tag=gallery-dl-config
php artisan gallery-dl:install
```

The install command downloads the standalone binary into `tools/gallery-dl` by default.
You can install to a custom directory with `php artisan gallery-dl:install --path=some/dir`.

## Configuration

Published config: `config/gallery-dl.php`

```php
return [
    'binary_path' => base_path('tools/gallery-dl' . (PHP_OS_FAMILY === 'Windows' ? '.exe' : '.bin')),
    'binary_download_url' => env(
        'GALLERY_DL_BINARY_DOWNLOAD_URL',
        PHP_OS_FAMILY === 'Windows'
            ? 'https://github.com/mikf/gallery-dl/releases/latest/download/gallery-dl.exe'
            : 'https://github.com/mikf/gallery-dl/releases/latest/download/gallery-dl.bin'
    ),
    'timeout' => 300,
];
```

## Usage

```php
use Nexxai\LaravelGallery\GalleryDlClient;

$gallery = app(GalleryDlClient::class);

// Basic binary check
$available = $gallery->isAvailable();

// Resolve profile entries as decoded JSON
$items = $gallery->resolve('https://www.instagram.com/some-profile/', dateAfter: now()->subDay());

// Pass a cookies file for this run
$items = $gallery->resolve(
    'https://www.instagram.com/some-profile/',
    dateAfter: now()->subDay(),
    cookiesPath: '/absolute/path/to/cookies.txt',
);
```

When no date is passed, all available items are fetched.
