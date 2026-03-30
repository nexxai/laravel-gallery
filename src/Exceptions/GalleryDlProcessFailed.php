<?php

namespace Nexxai\LaravelGallery\Exceptions;

use Nexxai\LaravelGallery\GalleryDlResult;

class GalleryDlProcessFailed extends GalleryDlException
{
    public function __construct(public readonly GalleryDlResult $result)
    {
        parent::__construct(
            trim('gallery-dl command failed. ' . $result->errorOutput),
            $result->exitCode,
        );
    }
}
