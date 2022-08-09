<?php declare(strict_types=1);

namespace Tests;

use Dive\TranslationImport\TranslationImportServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);
    }

    protected function getPackageProviders($app)
    {
        return [TranslationImportServiceProvider::class];
    }

    protected function setUpDatabase($app)
    {
        $app->make('db')->connection()->getSchemaBuilder()->dropAllTables();

        /*
        $laravel-csv-translation-import = require __DIR__ . '/../database/migrations/create_laravel_csv_translation_import_table.php.stub';
        $laravel-csv-translation-import->up();
        */
    }
}
