<?php

namespace Develoopin\MediaLibrary\Tests\TestSupport\testfiles;

use Develoopin\MediaLibrary\Conversions\Conversion;
use Develoopin\MediaLibrary\Conversions\DefaultConversionFileNamer;
use Develoopin\MediaLibrary\MediaCollections\Models\Media;

class TestConversionFileNamer extends DefaultConversionFileNamer
{
    public function getFileName(Conversion $conversion, Media $media): string
    {
        $fileName = pathinfo($media->file_name, PATHINFO_FILENAME);

        return "{$fileName}---{$conversion->getName()}";
    }
}
