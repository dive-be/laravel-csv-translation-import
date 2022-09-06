<?php declare(strict_types=1);

namespace Tests;

use function Pest\Laravel\artisan;

afterAll(function () {
    file_exists(config_path('lingo.php')) && unlink(config_path('lingo.php'));
});

it('copies the config', function () {
    artisan('lingo:install')->execute();

    expect(file_exists(config_path('lingo.php')))->toBeTrue();
});
