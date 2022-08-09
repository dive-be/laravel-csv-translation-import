<?php declare(strict_types=1);

namespace Tests;

use function Pest\Laravel\artisan;

afterAll(function () {
    file_exists(config_path('laravel-csv-translation-import.php')) && unlink(config_path('laravel-csv-translation-import.php'));
    array_map('unlink', glob(database_path('migrations/*_create_laravel_csv_translation_import_table.php')));
});

it('copies the config', function () {
    artisan('laravel-csv-translation-import:install')->execute();

    expect(
        file_exists(config_path('laravel-csv-translation-import.php'))
    )->toBeTrue();
});

it('copies the migration', function () {
    artisan('laravel-csv-translation-import:install')->execute();

    expect(
        glob(database_path('migrations/*_create_laravel_csv_translation_import_table.php'))
    )->toHaveCount(1);
});
