<?php declare(strict_types=1);

namespace Dive\TranslationImport\Commands;

use Illuminate\Console\Command;

class InstallPackageCommand extends Command
{
    protected $description = 'Install laravel-csv-translation-import.';

    protected $signature = 'laravel-csv-translation-import:install';

    public function handle(): int
    {
        if ($this->isHidden()) {
            $this->error('🤚  TranslationImport is already installed.');

            return self::FAILURE;
        }

        $this->line('🏎  Installing laravel-csv-translation-import...');
        $this->line('📑  Publishing configuration...');

        $this->call('vendor:publish', [
            '--provider' => "Dive\TranslationImport\TranslationImportServiceProvider",
            '--tag' => 'config',
        ]);

        $this->line('📑  Publishing migration...');

        $this->call('vendor:publish', [
            '--provider' => "Dive\TranslationImport\TranslationImportServiceProvider",
            '--tag' => 'migrations',
        ]);

        $this->info('🏁  TranslationImport installed successfully!');

        return self::SUCCESS;
    }

    public function isHidden(): bool
    {
        return file_exists(config_path('laravel-csv-translation-import.php'));
    }
}
