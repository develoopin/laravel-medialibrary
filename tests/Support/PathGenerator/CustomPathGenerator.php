<?php

namespace Develoopin\MediaLibrary\Tests\Support\PathGenerator;

use Develoopin\MediaLibrary\MediaCollections\Models\Media;
use Develoopin\MediaLibrary\Support\PathGenerator\PathGenerator;

class CustomPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return md5($media->id).'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media).'c/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media).'/cri/';
    }
}
