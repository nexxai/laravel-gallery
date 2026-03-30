<?php

namespace Nexxai\LaravelGallery\Facades;

use Illuminate\Support\Facades\Facade;

class GalleryDl extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'gallery-dl';
    }
}
