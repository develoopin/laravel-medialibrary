<?php

namespace Develoopin\MediaLibrary\Tests\Image;

use ReflectionClass;
use Develoopin\MediaLibrary\Support\ImageFactory;
use Develoopin\MediaLibrary\Tests\TestCase;

class ImageFactoryTest extends TestCase
{
    /** @test */
    public function loading_an_image_uses_the_correct_driver()
    {
        config(['media-library.image_driver' => 'imagick']);

        $image = ImageFactory::load($this->getTestJpg());

        $reflection = new ReflectionClass($image);

        $imageDriver = $reflection->getProperty('imageDriver');

        $imageDriver->setAccessible(true);

        $imageDriverValue = $imageDriver->getValue($image);

        $this->assertEquals('imagick', $imageDriverValue);
    }
}
