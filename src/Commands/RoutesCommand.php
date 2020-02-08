<?php

namespace Sluy\LaravelTranslation\Commands;

use Illuminate\Console\Command;
use Sluy\LaravelTranslation\LaravelTranslation;

/**
 * Artisan Command to add laravel-translation Routes.
 *
 * @author Stefan Luy<sluy1283@gmail.com>
 */
class RoutesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravel-translation:routes {--only=} {--path=}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add GUI Administration routes.';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(LaravelTranslation $trans)
    {
        $only = $this->option('only');
        $path = $this->option('path');
        if (!empty($only) && !in_array($only, ['web', 'api'])) {
            $this->error('Invalid --only option. Value must be "web" or "api".');
        }
        foreach(['web', 'api'] as $type) {
            if (empty($only) || $only === $type) {
                $this->warn("Installing '{$type}' routes...");
                $command = 'install' . \ucfirst($type) . 'Routes';
                $res = $trans->{$command}($path);
                $this->info("Stored in '{$res}'.");
            }
        }
        $this->info('Done.');
    }
}
