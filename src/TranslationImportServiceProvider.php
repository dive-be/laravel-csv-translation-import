<?php declare(strict_types=1);

namespace Dive\TranslationImport;

use Dive\TranslationImport\Commands\TranslationImportCommand;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class TranslationImportServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->registerCommands();
            $this->registerConfig();
            $this->registerMigration();
            $this->registerViews();
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-csv-translation-import');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-csv-translation-import.php', 'laravel-csv-translation-import');
    }

    private function registerCommands()
    {
        $this->commands([
            TranslationImportCommand::class,
        ]);
    }

    private function registerConfig()
    {
        $config = 'laravel-csv-translation-import.php';

        $this->publishes([
            __DIR__.'/../config/'.$config => $this->app->configPath($config),
        ], 'config');
    }

    private function registerMigration()
    {
        $migration = 'create_laravel_csv_translation_import_table.php';
        $doesntExist = Collection::make(glob($this->app->databasePath('migrations/*.php')))
            ->every(fn ($filename) => ! str_ends_with($filename, $migration));

        if ($doesntExist) {
            $timestamp = date('Y_m_d_His', time());
            $stub = __DIR__."/../database/migrations/{$migration}.stub";

            $this->publishes([
                $stub => $this->app->databasePath("migrations/{$timestamp}_{$migration}"),
            ], 'migrations');
        }
    }

    private function registerViews()
    {
        $this->publishes([
            __DIR__.'/../resources/views' => $this->app->basePath('resources/views/vendor/laravel-csv-translation-import'),
        ], 'views');
    }
}
