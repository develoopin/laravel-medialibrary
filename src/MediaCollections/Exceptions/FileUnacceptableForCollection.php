<?php

namespace Develoopin\MediaLibrary\MediaCollections\Exceptions;

use Develoopin\MediaLibrary\HasMedia;
use Develoopin\MediaLibrary\MediaCollections\File;
use Develoopin\MediaLibrary\MediaCollections\MediaCollection;

class FileUnacceptableForCollection extends FileCannotBeAdded
{
//    public static function create(File $file, MediaCollection $mediaCollection, HasMedia $hasMedia): self
//    {
//        $modelType = get_class($hasMedia);
//
//        return new static("The file with properties `{$file}` was not accepted into the collection named `{$mediaCollection->name}` of model `{$modelType}` with id `{$hasMedia->getKey()}`");
//    }

    /**
     * @param  File $file
     * @param  MediaCollection $mediaCollection
     * @param  HasMedia|null $hasMedia
     * @return FileUnacceptableForCollection
     */
    public static function create(File $file, MediaCollection $mediaCollection, $hasMedia = null)
    {
        $message = "The file with properties `{$file}` was not accepted into the collection named `{$mediaCollection->name}`";

        if ($hasMedia) {
            $modelType = get_class($hasMedia);
            $message .= " of model `{$modelType}` with id `{$hasMedia->getKey()}`";
        }

        return new static($message);
    }
}
