<?php

namespace Develoopin\MediaLibrary\MediaCollections\Models;

use DateTimeInterface;
use Develoopin\MediaLibrary\MediaCollections\FileAdderFactory;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Develoopin\MediaLibrary\Conversions\Conversion;
use Develoopin\MediaLibrary\Conversions\ConversionCollection;
use Develoopin\MediaLibrary\Conversions\ImageGenerators\ImageGeneratorFactory;
use Develoopin\MediaLibrary\HasMedia;
use Develoopin\MediaLibrary\MediaCollections\Filesystem;
use Develoopin\MediaLibrary\MediaCollections\HtmlableMedia;
use Develoopin\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Develoopin\MediaLibrary\MediaCollections\Models\Concerns\CustomMediaProperties;
use Develoopin\MediaLibrary\MediaCollections\Models\Concerns\HasUuid;
use Develoopin\MediaLibrary\MediaCollections\Models\Concerns\IsSorted;
use Develoopin\MediaLibrary\ResponsiveImages\RegisteredResponsiveImages;
use Develoopin\MediaLibrary\Support\File;
use Develoopin\MediaLibrary\Support\TemporaryDirectory;
use Develoopin\MediaLibrary\Support\UrlGenerator\UrlGeneratorFactory;

class Media extends Model implements Responsable, Htmlable
{
    use IsSorted,
        CustomMediaProperties,
        HasUuid;

    protected $table = 'media';

    const TYPE_OTHER = 'other';

    protected $guarded = [];

    /** @var array */
    public $mediaConversions = [];

    /** @var array */
    public $mediaCollections = [];


    protected $casts = [
        'manipulations' => 'array',
        'custom_properties' => 'array',
        'responsive_images' => 'array',
    ];

    /**
     * Register global media collections.
     *
     * @return void
     */
    public function registerMediaCollections()
    {
        // ...
    }

    /**
     * Add a media collection.
     *
     * @param string $name
     * @return MediaCollection
     */
    public function addMediaCollection(string $name): MediaCollection
    {
        $mediaCollection = MediaCollection::create($name);

        $this->mediaCollections[$name] = $mediaCollection;

        return $mediaCollection;
    }

    /**
     * Clear the media's entire media collection.
     *
     * @param null $model
     * @param \Spatie\MediaLibrary\Models\Media[]|\Illuminate\Support\Collection $excludedMedia
     * @return $this
     */
    public function clearMediaCollection($model = null, $excludedMedia = [])
    {
        if ($model) {
            $query = $model->media();
        } else {
            $query = static::query()->whereNull('model_type')->whereNull('model_id');
        }

        $query->where('collection_name', $this->collection_name);

        $excludedMedia = Collection::wrap($excludedMedia);

        if (! $excludedMedia->isEmpty()) {
            $query->whereNotIn('id', $excludedMedia->pluck('id')->all());
        }

        // Chunk query for performance
        $query->orderBy('id')->chunkById(100, function ($media) {
            $media->each->delete();
        });

        if (optional($model)->relationLoaded('media')) {
            unset($model->media);
        }

        if ($excludedMedia->isEmpty()) {
            event(new CollectionHasBeenCleared($this->collection_name, $model));
        }

        return $this;
    }

    /**
     * Register all media conversions.
     *
     * @return void
     */
    public function registerAllMediaConversions()
    {
        $this->registerMediaCollections();

        collect($this->mediaCollections)->each(function (MediaCollection $mediaCollection) {
            $actualMediaConversions = $this->mediaConversions;

            $this->mediaConversions = [];

            ($mediaCollection->mediaConversionRegistrations)($this);

            $preparedMediaConversions = collect($this->mediaConversions)
                ->each(function (Conversion $conversion) use ($mediaCollection) {
                    $conversion->performOnCollections($mediaCollection->name);
                })
                ->values()
                ->toArray();

            $this->mediaConversions = array_merge($actualMediaConversions, $preparedMediaConversions);
        });

        $this->registerMediaConversions($this);
    }

    /**
     * Register global media conversions.
     *
     * @param  Media|null  $media
     * @return void
     */
    public function registerMediaConversions(Media $media = null)
    {
        // ...
    }

    /**
     * Add a conversion.
     *
     * @param string $name
     * @return Conversion
     */
    public function addMediaConversion(string $name): Conversion
    {
        $conversion = Conversion::create($name);

        $this->mediaConversions[$name] = $conversion;

        return $conversion;
    }

    public function newCollection(array $models = [])
    {
        return new MediaCollection($models);
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Determine whether the media is associated with a model, or not.
     *
     * @return bool
     */
    public function hasModel()
    {
        return ! (is_null($this->model_type) || is_null($this->model_id));
    }

    /**
     * Add media for the given file.
     *
     * @param string|\Symfony\Component\HttpFoundation\File\UploadedFile $file
     * @return \Develoopin\MediaLibrary\FileAdder\FileAdder
     */
    public static function add($file)
    {
        return app(FileAdderFactory::class)->create($file);
    }

    /**
     * Add media from the current request.
     *
     * @param string $key
     * @return \Develoopin\MediaLibrary\FileAdder\FileAdder
     */
    public static function addFromRequest($key)
    {
        return app(FileAdderFactory::class)->createFromRequest($key);
    }

    /**
     * Add multiple media from the current request.
     *
     * @param array $keys
     * @return \Develoopin\MediaLibrary\FileAdder\FileAdder[]
     */
    public static function addMultipleFromRequest(array $keys)
    {
        return app(FileAdderFactory::class)->createMultipleFromRequest($keys);
    }

    /**
     * Add all media from the current request.
     *
     * @return \Develoopin\MediaLibrary\FileAdder\FileAdder[]
     */
    public static function addAllMediaFromRequest()
    {
        return app(FileAdderFactory::class)->createAllFromRequest();
    }


    public function getFullUrl(string $conversionName = ''): string
    {
        return url($this->getUrl($conversionName));
    }

    public function getUrl(string $conversionName = ''): string
    {
        $urlGenerator = UrlGeneratorFactory::createForMedia($this, $conversionName);

        return $urlGenerator->getUrl();
    }

    public function getTemporaryUrl(DateTimeInterface $expiration, string $conversionName = '', array $options = []): string
    {
        $urlGenerator = UrlGeneratorFactory::createForMedia($this, $conversionName);

        return $urlGenerator->getTemporaryUrl($expiration, $options);
    }

    public function getPath(string $conversionName = ''): string
    {
        $urlGenerator = UrlGeneratorFactory::createForMedia($this, $conversionName);

        return $urlGenerator->getPath();
    }

    public function getTypeAttribute(): string
    {
        $type = $this->getTypeFromExtension();

        if ($type !== self::TYPE_OTHER) {
            return $type;
        }

        return $this->getTypeFromMime();
    }

    public function getTypeFromExtension(): string
    {
        $imageGenerator = ImageGeneratorFactory::forExtension($this->extension);

        return $imageGenerator
            ? $imageGenerator->getType()
            : static::TYPE_OTHER;
    }

    public function getTypeFromMime(): string
    {
        $imageGenerator = ImageGeneratorFactory::forMimeType($this->mime_type);

        return $imageGenerator
            ? $imageGenerator->getType()
            : static::TYPE_OTHER;
    }

    public function getExtensionAttribute(): string
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }

    public function getHumanReadableSizeAttribute(): string
    {
        return File::getHumanReadableSize($this->size);
    }

    public function getDiskDriverName(): string
    {
        return strtolower(config("filesystems.disks.{$this->disk}.driver"));
    }

    public function getConversionsDiskDriverName(): string
    {
        $diskName = $this->conversions_disk ?? $this->disk;

        return strtolower(config("filesystems.disks.{$diskName}.driver"));
    }

    public function hasCustomProperty(string $propertyName): bool
    {
        return Arr::has($this->custom_properties, $propertyName);
    }

    /**
     * Get the value of custom property with the given name.
     *
     * @param string $propertyName
     * @param mixed $default
     *
     * @return mixed
     */
    public function getCustomProperty(string $propertyName, $default = null)
    {
        return Arr::get($this->custom_properties, $propertyName, $default);
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return $this
     */
    public function setCustomProperty(string $name, $value): self
    {
        $customProperties = $this->custom_properties;

        Arr::set($customProperties, $name, $value);

        $this->custom_properties = $customProperties;

        return $this;
    }

    public function forgetCustomProperty(string $name): self
    {
        $customProperties = $this->custom_properties;

        Arr::forget($customProperties, $name);

        $this->custom_properties = $customProperties;

        return $this;
    }

    public function getMediaConversionNames(): array
    {
        $conversions = ConversionCollection::createForMedia($this);

        return $conversions->map(fn (Conversion $conversion) => $conversion->getName())->toArray();
    }

    public function hasGeneratedConversion(string $conversionName): bool
    {
        $generatedConversions = $this->getGeneratedConversions();

        return $generatedConversions[$conversionName] ?? false;
    }

    public function markAsConversionGenerated(string $conversionName, bool $generated): self
    {
        $this->setCustomProperty("generated_conversions.{$conversionName}", $generated);

        $this->save();

        return $this;
    }

    public function getGeneratedConversions(): Collection
    {
        return collect($this->getCustomProperty('generated_conversions', []));
    }

    public function toResponse($request)
    {
        $downloadHeaders = [
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-Type' => $this->mime_type,
            'Content-Length' => $this->size,
            'Content-Disposition' => 'attachment; filename="'.$this->file_name.'"',
            'Pragma' => 'public',
        ];

        return response()->stream(function () {
            $stream = $this->stream();

            fpassthru($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, $downloadHeaders);
    }

    public function getResponsiveImageUrls(string $conversionName = ''): array
    {
        return $this->responsiveImages($conversionName)->getUrls();
    }

    public function hasResponsiveImages(string $conversionName = ''): bool
    {
        return count($this->getResponsiveImageUrls($conversionName)) > 0;
    }

    public function getSrcset(string $conversionName = ''): string
    {
        return $this->responsiveImages($conversionName)->getSrcset();
    }

    public function move(HasMedia $model, $collectionName = 'default', string $diskName = ''): self
    {
        $newMedia = $this->copy($model, $collectionName, $diskName);

        $this->delete();

        return $newMedia;
    }

    public function copy(HasMedia $model, $collectionName = 'default', string $diskName = ''): self
    {
        $temporaryDirectory = TemporaryDirectory::create();

        $temporaryFile = $temporaryDirectory->path('/').DIRECTORY_SEPARATOR.$this->file_name;

        /** @var \Develoopin\MediaLibrary\MediaCollections\Filesystem $filesystem */
        $filesystem = app(Filesystem::class);

        $filesystem->copyFromMediaLibrary($this, $temporaryFile);

        $newMedia = $model
            ->addMedia($temporaryFile)
            ->usingName($this->name)
            ->withCustomProperties($this->custom_properties)
            ->toMediaCollection($collectionName, $diskName);

        $temporaryDirectory->delete();

        return $newMedia;
    }

    public function responsiveImages(string $conversionName = ''): RegisteredResponsiveImages
    {
        return new RegisteredResponsiveImages($this, $conversionName);
    }

    public function stream()
    {
        /** @var \Develoopin\MediaLibrary\MediaCollections\Filesystem $filesystem */
        $filesystem = app(Filesystem::class);

        return $filesystem->getStream($this);
    }

    public function toHtml()
    {
        return $this->img()->toHtml();
    }

    public function img(string $conversionName = '', $extraAttributes = []): HtmlableMedia
    {
        return (new HtmlableMedia($this))
            ->conversion($conversionName)
            ->attributes($extraAttributes);
    }

    public function __invoke(...$arguments): HtmlableMedia
    {
        return $this->img(...$arguments);
    }
}
