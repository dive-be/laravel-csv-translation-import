<?php

namespace Dive\TranslationImport;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;

class Translations
{
    public static function get(string $locale): Collection
    {
        $path = lang_path($locale);

        return self::getFilePaths($path)
            ->mapWithKeys(static fn($file) => [$file => Lang::get($file, [], $locale)])
            ->filter(static fn($trans) => is_array($trans) && !empty($trans))
            ->map(static fn($trans, $key) => collect(Arr::dot($trans)))
            ->flatMap(static function ($translations, $file) {
                return $translations->mapWithKeys(function ($value, $key) use ($file) {
                    return [implode('.', [$file, $key]) => $value];
                });
            });
    }

    private static function getFilePaths(string $path = null): Collection
    {
        $excludeFiles = collect(config('csv-translation-import.exclude', []));

        return collect(File::allFiles($path))
            ->map(static fn($file) => ltrim(
                $file->getRelativePath() . '/' . $file->getFilenameWithoutExtension(),
                '/'
            ))
            ->filter(static fn($file) => !$excludeFiles->contains($file))
            ->flatten();
    }
}