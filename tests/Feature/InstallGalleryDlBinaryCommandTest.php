<?php

use Illuminate\Support\Facades\Http;

use function Pest\Laravel\artisan;

it('installs the binary into the project tools directory by default', function (): void {
    config()->set('gallery-dl.binary_download_url', 'https://example.test/gallery-dl');

    Http::fake([
        'example.test/*' => Http::response('binary-content', 200),
    ]);

    artisan('gallery-dl:install --force')->assertExitCode(0);

    $binaryPath = base_path('tools/gallery-dl'.(PHP_OS_FAMILY === 'Windows' ? '.exe' : ''));

    expect(is_file($binaryPath))->toBeTrue();
    expect(file_get_contents($binaryPath))->toBe('binary-content');

    Http::assertSentCount(1);

    @unlink($binaryPath);
    @rmdir(dirname($binaryPath));
});

it('installs the binary into a custom directory when path option is provided', function (): void {
    config()->set('gallery-dl.binary_download_url', 'https://example.test/gallery-dl');

    Http::fake([
        'example.test/*' => Http::response('custom-binary', 200),
    ]);

    $customDirectory = sys_get_temp_dir().'/gallery-tools-'.uniqid('', true);

    artisan('gallery-dl:install', [
        '--path' => $customDirectory,
        '--force' => true,
    ])->assertExitCode(0);

    $binaryPath = rtrim($customDirectory, DIRECTORY_SEPARATOR)
        .DIRECTORY_SEPARATOR
        .'gallery-dl'
        .(PHP_OS_FAMILY === 'Windows' ? '.exe' : '');

    expect(is_file($binaryPath))->toBeTrue();
    expect(file_get_contents($binaryPath))->toBe('custom-binary');

    Http::assertSentCount(1);

    @unlink($binaryPath);
    @rmdir(dirname($binaryPath));
});

it('fails when download response is unsuccessful', function (): void {
    config()->set('gallery-dl.binary_download_url', 'https://example.test/gallery-dl');

    Http::fake([
        'example.test/*' => Http::response('failed', 500),
    ]);

    artisan('gallery-dl:install --force')->assertExitCode(1);
});
