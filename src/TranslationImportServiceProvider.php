<?php declare(strict_types=1);

namespace Dive\TranslationImport;

use Dive\TranslationImport\Commands\InstallPackageCommand;
use Illuminate\Support\ServiceProvider;

class TranslationImportServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->registerConfig();
            $this->registerCommand();
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/csv-translation-import.php', 'csv-translation-import');
    }

    private function registerCommand()
    {
        $this->commands([
            InstallPackageCommand::class,
        ]);
    }

    private function registerConfig()
    {
        $config = 'csv-translation-import.php';

        $this->publishes([
            __DIR__.'/../config/'.$config => $this->app->configPath($config),
        ], 'config');
    }
}
