<?php declare(strict_types=1);

namespace Dive\Lingo\Commands;

use Illuminate\Console\Command;

class InstallPackageCommand extends Command
{
    protected $description = 'Installs Lingo.';

    protected $signature = 'lingo:install';

    public function handle(): int
    {
        if ($this->isHidden()) {
            $this->error('🤚  Lingo is already installed.');

            return self::FAILURE;
        }

        $this->line('🏎  Installing Lingo...');
        $this->line('📑  Publishing configuration...');

        $this->call('vendor:publish', [
            '--provider' => "Dive\Lingo\LingoServiceProvider",
            '--tag' => 'config',
        ]);

        $this->info('🏁  Lingo installed successfully!');

        return self::SUCCESS;
    }

    public function isHidden(): bool
    {
        return file_exists(config_path('lingo.php'));
    }
}
