<?php

namespace Develoopin\MediaLibrary\Tests\TestSupport\TestModels;

use Illuminate\Database\Eloquent\Model;
use Develoopin\MediaLibrary\HasMedia;
use Develoopin\MediaLibrary\InteractsWithMedia;

class TestModel extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('avatar')
            ->useFallbackUrl('/default.jpg')
            ->useFallbackPath('/default.jpg');
    }
}
