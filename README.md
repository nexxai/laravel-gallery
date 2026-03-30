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
    'binary_path' => base_path('tools/gallery-dl' . (PHP_OS_FAMILY === 'Windows' ? '.exe' : '')),
    'binary_download_url' => 'https://github.com/mikf/gallery-dl/releases/latest/download/gallery-dl',
    'download_path' => storage_path('gallery'),
    'timeout' => 300,
    'auto_create_download_path' => true,
];
```

## Usage

```php
use Nexxai\LaravelGallery\GalleryDlClient;

$gallery = app(GalleryDlClient::class);

// Basic binary check
$available = $gallery->isAvailable();

// Return media URLs only (gallery-dl -G)
$urls = $gallery->getUrls('https://www.instagram.com/some-profile/', now()->subDay());

// Download media into configured download_path
$result = $gallery->download('https://www.instagram.com/some-profile/', now()->subDay());

// Pass a cookies file
$result = $gallery->download(
    'https://www.instagram.com/some-profile/',
    now()->subDay(),
    '/absolute/path/to/cookies.txt',
);
```

When no date is passed, all available items are fetched.
