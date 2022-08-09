<?php declare(strict_types=1);

namespace Tests;

use function Pest\Laravel\artisan;

afterAll(function () {
    file_exists(config_path('csv-translation-import.php')) && unlink(config_path('csv-translation-import.php'));
});

it('copies the config', function () {
    artisan('laravel-csv-translation-import:install')->execute();

    expect(
        file_exists(config_path('csv-translation-import.php'))
    )->toBeTrue();
});
