<?php

namespace Develoopin\MediaLibrary\MediaCollections\Events;

use Illuminate\Queue\SerializesModels;
use Develoopin\MediaLibrary\MediaCollections\Models\Media;

class MediaHasBeenAdded
{
    use SerializesModels;

    public Media $media;

    public function __construct(Media $media)
    {
        $this->media = $media;
    }
}
