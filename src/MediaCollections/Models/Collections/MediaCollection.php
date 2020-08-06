<?php

namespace Develoopin\MediaLibrary\MediaCollections\Models\Collections;

use Illuminate\Database\Eloquent\Collection;

class MediaCollection extends Collection
{
    public function totalSizeInBytes(): int
    {
        return $this->sum('size');
    }
}
