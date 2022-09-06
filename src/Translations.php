<?php

namespace Dive\Lingo;

use Dive\Crowbar\Crowbar;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class Translations
{
    public static function get(string $locale, ?string $alternatePath): Collection
    {
        $originalPath = lang_path();

        if ($alternatePath != null) {
            self::setTranslatorPath($alternatePath);
        }

        $paths = self::getFilePaths(lang_path($locale))
            ->mapWithKeys(static function ($file) use ($locale) {
                $translations = __($file, [], $locale);
                return [
                    $file => self::normalizeTranslations($translations)
                ];
            })
            ->filter(static fn($trans) => is_array($trans) && !empty($trans))
            ->map(static fn($trans, $key) => collect(Arr::dot($trans)))
            ->flatMap(static function ($translations, $file) {
                return $translations->mapWithKeys(function ($value, $key) use ($file) {
                    return [implode('-', [$file, $key]) => $value];
                });
            });

        if ($alternatePath != null) {
            self::setTranslatorPath($originalPath);
        }

        return $paths;
    }

    private static function normalizeTranslations(array &$items): array
    {
        foreach ($items as $_ => &$value) {
            if (is_array($value)) {
                self::normalizeTranslations($value);
            } else {
                $value = preg_replace('/\s+/S', ' ', preg_replace("/[\\n\\r]+/", '', $value));
            }
        }

        return $items;
    }

    private static function getFilePaths(string $path = null): Collection
    {
        $excludeFiles = collect(config('lingo.exclude', []));

        return collect(File::allFiles($path))
            ->map(static fn($file) => ltrim(
                $file->getRelativePath() . '/' . $file->getFilenameWithoutExtension(),
                '/'
            ))
            ->filter(static fn($file) => !$excludeFiles->contains($file))
            ->flatten();
    }

    private static function setTranslatorPath(string $path)
    {
        app()->useLangPath($path);
        Crowbar::pry(app('translation.loader'))->path = $path;
        app('translator')->setLoaded([]);
    }
}