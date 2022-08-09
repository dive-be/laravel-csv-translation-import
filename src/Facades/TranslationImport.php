<?php declare(strict_types=1);

namespace Dive\TranslationImport\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Dive\TranslationImport\TranslationImport
 */
class TranslationImport extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-csv-translation-import';
    }
}
