<?php

namespace Develoopin\MediaLibrary\MediaCollections;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Develoopin\MediaLibrary\MediaCollections\Exceptions\RequestDoesNotHaveFile;
use Develoopin\MediaLibrary\Support\RemoteFile;

class FileAdderFactory
{
    /**
//     * @param \Illuminate\Database\Eloquent\Model $subject
//     * @param string|\Symfony\Component\HttpFoundation\File\UploadedFile $file
//     *
//     * @return \Spatie\MediaLibrary\MediaCollections\FileAdder
//     */
//    public static function create(Model $subject, $file): FileAdder
    /**
     * @param string|\Symfony\Component\HttpFoundation\File\UploadedFile $file
     * @param \Illuminate\Database\Eloquent\Model|null $subject
     *
     * @return \Develoopin\MediaLibrary\FileAdder\FileAdder
     */
    public static function create($file, $subject = null)
    {
        /** @var \Develoopin\MediaLibrary\MediaCollections\FileAdder $fileAdder */
        $fileAdder = app(FileAdder::class);

        return $fileAdder
            ->setSubject($subject)
            ->setFile($file);
    }

//    public static function createFromDisk(Model $subject, string $key, string $disk): FileAdder
    public static function createFromDisk(string $key, string $disk, $subject = null): FileAdder
    {
        /** @var \Develoopin\MediaLibrary\MediaCollections\FileAdder $fileAdder */
        $fileAdder = app(FileAdder::class);

        return $fileAdder
            ->setSubject($subject)
            ->setFile(new RemoteFile($key, $disk));
    }

//    public static function createFromRequest(Model $subject, string $key): FileAdder
    public static function createFromRequest(string $key, $subject = null): FileAdder
    {
        return static::createMultipleFromRequest($subject, [$key])->first();
    }

//    public static function createMultipleFromRequest(Model $subject, array $keys = []): Collection
    public static function createMultipleFromRequest(array $keys = [], $subject = null): Collection

    {
        return collect($keys)
            ->map(function (string $key) use ($subject) {
                $search = ['[', ']', '"', "'"];
                $replace = ['.', '', '', ''];

                $key = str_replace($search, $replace, $key);

                if (! request()->hasFile($key)) {
                    throw RequestDoesNotHaveFile::create($key);
                }

                $files = request()->file($key);

                if (! is_array($files)) {
//                    return static::create($subject, $files);
                    return static::create($files, $subject);
                }

//                return array_map(fn ($file) => static::create($subject, $file), $files);
                return array_map(function ($file) use ($subject) {
                    return static::create($file, $subject);
                }, $files);
            })->flatten();
    }

//    public static function createAllFromRequest(Model $subject): Collection
    public static function createAllFromRequest($subject = null): Collection
    {
        $fileKeys = array_keys(request()->allFiles());

//        return static::createMultipleFromRequest($subject, $fileKeys);
        return static::createMultipleFromRequest($fileKeys, $subject);
    }
}
