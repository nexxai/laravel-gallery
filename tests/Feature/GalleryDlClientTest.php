<?php

use Illuminate\Process\Factory;
use Nexxai\LaravelGallery\Exceptions\GalleryDlBinaryNotFound;
use Nexxai\LaravelGallery\GalleryDlClient;

it('returns only urls when using getUrls', function (): void {
    $binaryPath = tempnam(sys_get_temp_dir(), 'gallery-dl-bin-');
    file_put_contents($binaryPath, '#!/bin/sh' . PHP_EOL);
    chmod($binaryPath, 0755);

    $downloadPath = sys_get_temp_dir() . '/gallery-downloads-' . uniqid('', true);

    $process = new Factory();
    $process->fake([
        '*' => $process->sequence([
            $process->result('1.31.10', '', 0),
            $process->result("https://example.com/a.jpg\nnot-a-url\nhttps://example.com/b.png\n", '', 0),
        ]),
    ]);

    $client = new GalleryDlClient([
        'binary_path' => $binaryPath,
        'download_path' => $downloadPath,
        'timeout' => 30,
    ], $process);

    $urls = $client->getUrls('https://instagram.com/some-profile', '20250101');

    expect($urls)->toBe([
        'https://example.com/a.jpg',
        'https://example.com/b.png',
    ]);

    $process->assertRan(function ($pendingProcess) use ($binaryPath): bool {
        return is_array($pendingProcess->command)
            && $pendingProcess->command[0] === $binaryPath
            && in_array('-G', $pendingProcess->command, true)
            && in_array('--date-after', $pendingProcess->command, true)
            && in_array('20250101', $pendingProcess->command, true)
            && in_array('--dest', $pendingProcess->command, true);
    });

    @unlink($binaryPath);
});

it('creates download directory and includes cookies when downloading', function (): void {
    $binaryPath = tempnam(sys_get_temp_dir(), 'gallery-dl-bin-');
    $cookiesPath = tempnam(sys_get_temp_dir(), 'gallery-cookies-');
    file_put_contents($binaryPath, '#!/bin/sh' . PHP_EOL);
    chmod($binaryPath, 0755);

    $downloadPath = sys_get_temp_dir() . '/gallery-downloads-' . uniqid('', true);

    $process = new Factory();
    $process->fake([
        '*' => $process->sequence([
            $process->result('1.31.10', '', 0),
            $process->result('done', '', 0),
        ]),
    ]);

    $client = new GalleryDlClient([
        'binary_path' => $binaryPath,
        'download_path' => $downloadPath,
        'cookies_path' => $cookiesPath,
        'timeout' => 30,
        'auto_create_download_path' => true,
    ], $process);

    $result = $client->download('https://instagram.com/some-profile', new DateTimeImmutable('2025-02-01'));

    expect($result->successful())->toBeTrue();
    expect(is_dir($downloadPath))->toBeTrue();

    $process->assertRan(function ($pendingProcess) use ($binaryPath, $cookiesPath): bool {
        return is_array($pendingProcess->command)
            && $pendingProcess->command[0] === $binaryPath
            && in_array('--cookies', $pendingProcess->command, true)
            && in_array($cookiesPath, $pendingProcess->command, true)
            && in_array('--date-after', $pendingProcess->command, true)
            && in_array('20250201', $pendingProcess->command, true)
            && ! in_array('-G', $pendingProcess->command, true);
    });

    @unlink($binaryPath);
    @unlink($cookiesPath);
    @rmdir($downloadPath);
});

it('fails availability checks when binary is missing', function (): void {
    $client = new GalleryDlClient([
        'binary_path' => '/tmp/does-not-exist/gallery-dl',
        'download_path' => sys_get_temp_dir() . '/gallery-downloads-' . uniqid('', true),
    ]);

    expect($client->isAvailable())->toBeFalse();
    expect(fn () => $client->assertAvailable())->toThrow(GalleryDlBinaryNotFound::class);
});
