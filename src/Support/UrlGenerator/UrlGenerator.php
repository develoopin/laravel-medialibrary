<?php

namespace Develoopin\MediaLibrary\Support\UrlGenerator;

use DateTimeInterface;
use Develoopin\MediaLibrary\Conversions\Conversion;
use Develoopin\MediaLibrary\MediaCollections\Models\Media;
use Develoopin\MediaLibrary\Support\PathGenerator\PathGenerator;

interface UrlGenerator
{
    public function getUrl(): string;

    public function getPath(): string;

    public function setMedia(Media $media): self;

    public function setConversion(Conversion $conversion): self;

    public function setPathGenerator(PathGenerator $pathGenerator): self;

    public function getTemporaryUrl(DateTimeInterface $expiration, array $options = []): string;

    public function getResponsiveImagesDirectoryUrl(): string;
}
