<?php

namespace Nexxai\LaravelGallery\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class InstallGalleryDlBinaryCommand extends Command
{
    protected $signature = 'gallery-dl:install {--path= : Directory where gallery-dl should be installed (defaults to project tools folder)} {--force : Overwrite existing binary}';

    protected $description = 'Download the gallery-dl standalone binary into the configured tools directory';

    public function handle(): int
    {
        $downloadUrl = (string) config('gallery-dl.binary_download_url');
        $binaryPath = $this->binaryPath();

        if ($downloadUrl === '') {
            $this->error('Missing gallery-dl.binary_download_url configuration.');

            return self::FAILURE;
        }

        if (is_file($binaryPath) && ! $this->option('force')) {
            $this->info("gallery-dl already exists at {$binaryPath}. Use --force to overwrite.");

            return self::SUCCESS;
        }

        $directory = dirname($binaryPath);

        if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
            $this->error("Unable to create directory: {$directory}");

            return self::FAILURE;
        }

        $this->line("Downloading gallery-dl from {$downloadUrl} ...");

        $response = Http::timeout(60)->get($downloadUrl);

        if (! $response->successful()) {
            $this->error('Download failed. Check the URL or set gallery-dl.binary_download_url.');

            return self::FAILURE;
        }

        $contents = $response->body();

        if (@file_put_contents($binaryPath, $contents) === false) {
            $this->error("Unable to write binary to {$binaryPath}");

            return self::FAILURE;
        }

        @chmod($binaryPath, 0755);

        $this->info("gallery-dl installed at {$binaryPath}");

        if ($binaryPath !== (string) config('gallery-dl.binary_path')) {
            $this->warn('If this differs from gallery-dl.binary_path, update config/gallery-dl.php to match.');
        }

        $this->warn('Consider adding /tools to your .gitignore so binaries are not committed.');

        return self::SUCCESS;
    }

    private function binaryPath(): string
    {
        $directory = $this->option('path');
        $directory = is_string($directory) && trim($directory) !== ''
            ? trim($directory)
            : base_path('tools');

        if (! $this->isAbsolutePath($directory)) {
            $directory = base_path($directory);
        }

        return rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->binaryFilename();
    }

    private function binaryFilename(): string
    {
        return PHP_OS_FAMILY === 'Windows' ? 'gallery-dl.exe' : 'gallery-dl';
    }

    private function isAbsolutePath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        if ($path[0] === '/' || $path[0] === '\\') {
            return true;
        }

        return (bool) preg_match('/^[A-Za-z]:\\\\/', $path);
    }
}
