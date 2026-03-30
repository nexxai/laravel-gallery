<?php

namespace Nexxai\LaravelGallery;

use DateTimeInterface;
use Illuminate\Process\Factory;
use Nexxai\LaravelGallery\Exceptions\GalleryDlBinaryNotFound;
use Nexxai\LaravelGallery\Exceptions\GalleryDlException;
use Nexxai\LaravelGallery\Exceptions\GalleryDlProcessFailed;

class GalleryDlClient
{
    private readonly Factory $process;

    public function __construct(private readonly array $config = [], ?Factory $process = null)
    {
        $this->process = $process ?? new Factory();
    }

    public function isAvailable(): bool
    {
        $binaryPath = $this->binaryPath();

        if (! is_file($binaryPath) || ! is_executable($binaryPath)) {
            return false;
        }

        return $this->process
            ->timeout(10)
            ->run([$binaryPath, '--version'])
            ->successful();
    }

    public function assertAvailable(): void
    {
        if ($this->isAvailable()) {
            return;
        }

        throw new GalleryDlBinaryNotFound(
            sprintf(
                'gallery-dl binary is not available at [%s]. Run `php artisan gallery-dl:install` or configure gallery-dl.binary_path.',
                $this->binaryPath(),
            )
        );
    }

    public function getUrls(string $url, DateTimeInterface|string|null $dateAfter = null, ?string $cookiesPath = null): array
    {
        $result = $this->run(
            array_merge(['-G'], $this->dateAfterArguments($dateAfter), [$url]),
            $cookiesPath,
        );

        if (! $result->successful()) {
            throw new GalleryDlProcessFailed($result);
        }

        return array_values(array_filter(
            $result->outputLines(),
            fn (string $line): bool => (bool) filter_var($line, FILTER_VALIDATE_URL),
        ));
    }

    public function download(string $url, DateTimeInterface|string|null $dateAfter = null, ?string $cookiesPath = null): GalleryDlResult
    {
        $this->ensureDownloadDirectoryExists();

        $result = $this->run(
            array_merge($this->dateAfterArguments($dateAfter), [$url]),
            $cookiesPath,
        );

        if (! $result->successful()) {
            throw new GalleryDlProcessFailed($result);
        }

        return $result;
    }

    public function binaryPath(): string
    {
        $path = (string) ($this->config['binary_path'] ?? '');

        if ($path === '') {
            throw new GalleryDlException('gallery-dl.binary_path is not configured.');
        }

        return $path;
    }

    private function run(array $arguments, ?string $cookiesPath = null): GalleryDlResult
    {
        $this->assertAvailable();

        $command = array_merge(
            [$this->binaryPath()],
            $this->baseArguments($cookiesPath),
            $arguments,
        );

        $process = $this->process
            ->timeout((int) ($this->config['timeout'] ?? 300))
            ->run($command);

        return new GalleryDlResult(
            command: $command,
            exitCode: $process->exitCode() ?? 1,
            output: $process->output(),
            errorOutput: $process->errorOutput(),
        );
    }

    private function baseArguments(?string $cookiesPath = null): array
    {
        $arguments = ['--dest', $this->downloadPath()];

        $cookiesPath = trim((string) $cookiesPath);

        if ($cookiesPath !== '') {
            $arguments[] = '--cookies';
            $arguments[] = $cookiesPath;
        }

        return $arguments;
    }

    private function dateAfterArguments(DateTimeInterface|string|null $dateAfter): array
    {
        if ($dateAfter === null || $dateAfter === '') {
            return [];
        }

        if ($dateAfter instanceof DateTimeInterface) {
            $dateAfter = $dateAfter->format('Ymd');
        }

        return ['--date-after', trim((string) $dateAfter)];
    }

    private function downloadPath(): string
    {
        $path = (string) ($this->config['download_path'] ?? '');

        if ($path === '') {
            throw new GalleryDlException('gallery-dl.download_path is not configured.');
        }

        return $path;
    }

    private function ensureDownloadDirectoryExists(): void
    {
        if (! ($this->config['auto_create_download_path'] ?? true)) {
            return;
        }

        $downloadPath = $this->downloadPath();

        if (is_dir($downloadPath)) {
            return;
        }

        if (@mkdir($downloadPath, 0755, true) || is_dir($downloadPath)) {
            return;
        }

        throw new GalleryDlException(sprintf('Unable to create download directory [%s].', $downloadPath));
    }
}
