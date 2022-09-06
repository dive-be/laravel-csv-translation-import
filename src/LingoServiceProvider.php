<?php declare(strict_types=1);

namespace Dive\Lingo;

use Dive\Lingo\Commands\InstallPackageCommand;
use Illuminate\Support\ServiceProvider;

class LingoServiceProvider extends ServiceProvider
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
        $this->mergeConfigFrom(__DIR__.'/../config/lingo.php', 'lingo');
    }

    private function registerCommand()
    {
        $this->commands([
            InstallPackageCommand::class,
        ]);
    }

    private function registerConfig()
    {
        $config = 'lingo.php';

        $this->publishes([
            __DIR__.'/../config/'.$config => $this->app->configPath($config),
        ], 'config');
    }
}
