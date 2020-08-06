<?php

namespace Develoopin\MediaLibrary\Tests\Feature\Media;

use Develoopin\MediaLibrary\MediaCollections\MediaRepository;
use Develoopin\MediaLibrary\Tests\TestCase;
use Develoopin\MediaLibrary\Tests\TestSupport\TestModels\TestCustomMediaModel;

class MediaRepositoryTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('media-library.media_model', TestCustomMediaModel::class);
    }

    /** @test */
    public function it_can_use_a_custom_media_model()
    {
        $this->testModel
            ->addMedia($this->getTestJpg())
            ->toMediaCollection();

        $mediaRepository = app(MediaRepository::class);

        $this->assertEquals(TestCustomMediaModel::class, $mediaRepository->all()->getQueueableClass());
    }
}
