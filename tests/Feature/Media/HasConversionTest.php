<?php

namespace Develoopin\MediaLibrary\Tests\Feature\Media;

use Develoopin\MediaLibrary\Tests\TestCase;

class HasConversionTest extends TestCase
{
    /** @test */
    public function test()
    {
        $media = $this->testModelWithConversion->addMedia($this->getTestJpg())->toMediaCollection();

        $this->assertTrue($media->hasGeneratedConversion('thumb'));
    }
}
