<?php

use Illuminate\Process\Factory;
use Nexxai\LaravelGallery\Exceptions\GalleryDlBinaryNotFound;
use Nexxai\LaravelGallery\Exceptions\GalleryDlException;
use Nexxai\LaravelGallery\GalleryDlClient;

it('resolves json response with date-after', function (): void {
    $binaryPath = tempnam(sys_get_temp_dir(), 'gallery-dl-bin-');
    file_put_contents($binaryPath, '#!/bin/sh' . PHP_EOL);
    chmod($binaryPath, 0755);

    $process = new Factory();
    $process->fake([
        '*' => $process->sequence([
            $process->result('1.31.10', '', 0),
            $process->result('[{"url":"https://example.com/a.jpg"},{"url":"https://example.com/b.png"}]', '', 0),
        ]),
    ]);

    $client = new GalleryDlClient([
        'binary_path' => $binaryPath,
        'timeout' => 30,
    ], $process);

    $resolved = $client->resolve('https://instagram.com/some-profile', '20250101');

    expect($resolved)->toBe([
        ['url' => 'https://example.com/a.jpg'],
        ['url' => 'https://example.com/b.png'],
    ]);

    $process->assertRan(function ($pendingProcess) use ($binaryPath): bool {
        return is_array($pendingProcess->command)
            && $pendingProcess->command[0] === $binaryPath
            && in_array('--resolve-json', $pendingProcess->command, true)
            && in_array('--no-download', $pendingProcess->command, true)
            && in_array('--date-after', $pendingProcess->command, true)
            && in_array('20250101', $pendingProcess->command, true)
            && ! in_array('--cookies', $pendingProcess->command, true);
    });

    @unlink($binaryPath);
});

it('includes cookies when resolving json response', function (): void {
    $binaryPath = tempnam(sys_get_temp_dir(), 'gallery-dl-bin-');
    $cookiesPath = tempnam(sys_get_temp_dir(), 'gallery-cookies-');
    file_put_contents($binaryPath, '#!/bin/sh' . PHP_EOL);
    chmod($binaryPath, 0755);

    $process = new Factory();
    $process->fake([
        '*' => $process->sequence([
            $process->result('1.31.10', '', 0),
            $process->result('{"ok":true}', '', 0),
        ]),
    ]);

    $client = new GalleryDlClient([
        'binary_path' => $binaryPath,
        'timeout' => 30,
    ], $process);

    $resolved = $client->resolve('https://instagram.com/some-profile', new DateTimeImmutable('2025-02-01'), $cookiesPath);

    expect($resolved)->toBe(['ok' => true]);

    $process->assertRan(function ($pendingProcess) use ($binaryPath, $cookiesPath): bool {
        return is_array($pendingProcess->command)
            && $pendingProcess->command[0] === $binaryPath
            && in_array('--resolve-json', $pendingProcess->command, true)
            && in_array('--no-download', $pendingProcess->command, true)
            && in_array('--cookies', $pendingProcess->command, true)
            && in_array($cookiesPath, $pendingProcess->command, true)
            && in_array('--date-after', $pendingProcess->command, true)
            && in_array('20250201', $pendingProcess->command, true)
            && ! in_array('-G', $pendingProcess->command, true)
            && ! in_array('--dest', $pendingProcess->command, true);
    });

    @unlink($binaryPath);
    @unlink($cookiesPath);
});

it('throws when gallery-dl returns invalid json', function (): void {
    $binaryPath = tempnam(sys_get_temp_dir(), 'gallery-dl-bin-');
    file_put_contents($binaryPath, '#!/bin/sh' . PHP_EOL);
    chmod($binaryPath, 0755);

    $process = new Factory();
    $process->fake([
        '*' => $process->sequence([
            $process->result('1.31.10', '', 0),
            $process->result('not-json', '', 0),
        ]),
    ]);

    $client = new GalleryDlClient([
        'binary_path' => $binaryPath,
        'timeout' => 30,
    ], $process);

    expect(fn () => $client->resolve('https://instagram.com/some-profile'))->toThrow(GalleryDlException::class);

    @unlink($binaryPath);
});

it('fails availability checks when binary is missing', function (): void {
    $client = new GalleryDlClient([
        'binary_path' => '/tmp/does-not-exist/gallery-dl',
    ]);

    expect($client->isAvailable())->toBeFalse();
    expect(fn () => $client->assertAvailable())->toThrow(GalleryDlBinaryNotFound::class);
});
