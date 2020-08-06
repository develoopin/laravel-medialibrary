<?php

namespace Develoopin\MediaLibrary\MediaCollections\Events;

use Illuminate\Queue\SerializesModels;
use Develoopin\MediaLibrary\HasMedia;

class CollectionHasBeenCleared
{
    use SerializesModels;

//    public HasMedia $model;

    /** @var HasMedia|null */
    public $model;

    public string $collectionName;

//    public function __construct(HasMedia $model, string $collectionName)
//    {
//        $this->model = $model;
//
//        $this->collectionName = $collectionName;
//    }

    /**
     * Create a new instance.
     *
     * @param string $collectionName
     * @param HasMedia|null $model
     * @return void
     */
    public function __construct(string $collectionName, $model = null)
    {
        $this->collectionName = $collectionName;

        $this->model = $model;
    }
}
