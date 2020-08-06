<?php

namespace Develoopin\MediaLibrary\Support\PathGenerator;

use Develoopin\MediaLibrary\MediaCollections\Exceptions\InvalidPathGenerator;

class PathGeneratorFactory
{
    public static function create(): PathGenerator
    {
        $pathGeneratorClass = config('media-library.path_generator');

        static::guardAgainstInvalidPathGenerator($pathGeneratorClass);

        return app($pathGeneratorClass);
    }

    protected static function guardAgainstInvalidPathGenerator(string $pathGeneratorClass): void
    {
        if (! class_exists($pathGeneratorClass)) {
            throw InvalidPathGenerator::doesntExist($pathGeneratorClass);
        }

        if (! is_subclass_of($pathGeneratorClass, PathGenerator::class)) {
            throw InvalidPathGenerator::doesNotImplementPathGenerator($pathGeneratorClass);
        }
    }
}
