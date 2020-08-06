<?php

namespace Develoopin\MediaLibrary\Tests\TestSupport\TestModels;

use Develoopin\MediaLibrary\MediaCollections\Models\Media;

class TestModelWithConversionQueued extends TestModel
{
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(50);

        $this->addMediaConversion('keep_original_format')
            ->keepOriginalImageFormat();
    }
}
