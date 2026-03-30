<?php

namespace Tests;

use Nexxai\LaravelGallery\GalleryServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            GalleryServiceProvider::class,
        ];
    }
}
