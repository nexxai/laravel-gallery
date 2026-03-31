<?php

namespace Nexxai\LaravelGallery;

use DateTimeInterface;
use Illuminate\Process\Factory;
use JsonException;
use Nexxai\LaravelGallery\Exceptions\GalleryDlBinaryNotFound;
use Nexxai\LaravelGallery\Exceptions\GalleryDlException;
use Nexxai\LaravelGallery\Exceptions\GalleryDlProcessFailed;

class GalleryDlClient
{
    private readonly Factory $process;

    public function __construct(private readonly array $config = [], ?Factory $process = null)
    {
        $this->process = $process ?? new Factory;
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

    public function resolve(string $url, DateTimeInterface|string|null $dateAfter = null, ?string $cookiesPath = null): mixed
    {
        $result = $this->run(
            array_merge(['--resolve-json', '--no-download'], $this->dateAfterArguments($dateAfter), [$url]),
            $cookiesPath,
        );

        if (! $result->successful()) {
            throw new GalleryDlProcessFailed($result);
        }

        try {
            return json_decode($result->output, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new GalleryDlException('gallery-dl returned invalid JSON.', previous: $e);
        }
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
        $arguments = [];

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
}
