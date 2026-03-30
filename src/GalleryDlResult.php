<?php

namespace Nexxai\LaravelGallery;

class GalleryDlResult
{
    public function __construct(
        public readonly array $command,
        public readonly int $exitCode,
        public readonly string $output,
        public readonly string $errorOutput,
    ) {
    }

    public function successful(): bool
    {
        return $this->exitCode === 0;
    }

    public function outputLines(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', $this->output) ?: [])));
    }
}
