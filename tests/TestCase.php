<?php declare(strict_types=1);

namespace Tests;

use Dive\TranslationImport\TranslationImportServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use function Pest\testDirectory;

class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);

        $this->useTestLanguageFiles();
    }

    protected function getPackageProviders($app)
    {
        return [TranslationImportServiceProvider::class];
    }

    protected function setUpDatabase($app)
    {
        $app->make('db')->connection()->getSchemaBuilder()->dropAllTables();
    }

    private function useTestLanguageFiles()
    {
        $this->app->useLangPath(testDirectory('Files/lang'));
    }
}
