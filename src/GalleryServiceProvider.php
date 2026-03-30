<?php

namespace Nexxai\LaravelGallery;

use Illuminate\Process\Factory;
use Illuminate\Support\ServiceProvider;
use Nexxai\LaravelGallery\Commands\InstallGalleryDlBinaryCommand;

class GalleryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/gallery-dl.php', 'gallery-dl');

        $this->app->singleton(GalleryDlClient::class, function ($app): GalleryDlClient {
            return new GalleryDlClient(
                $app['config']->get('gallery-dl', []),
                $app->make(Factory::class),
            );
        });

        $this->app->alias(GalleryDlClient::class, 'gallery-dl');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/gallery-dl.php' => config_path('gallery-dl.php'),
        ], 'gallery-dl-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallGalleryDlBinaryCommand::class,
            ]);
        }
    }
}
