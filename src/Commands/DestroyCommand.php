<?php

namespace Sluy\LaravelTranslation\Commands;

use Illuminate\Console\Command;
use Sluy\LaravelTranslation\LaravelTranslation;

/**
 * Artisan Command to remove stored translations.
 *
 * @author Stefan Luy<sluy1283@gmail.com.
 */
class DestroyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravel-translation:destroy {driver} {--locale=} {--location=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete translations.';

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
        $cfg = [
            'driver' => $this->argument('driver'),
            'location' => $this->option('location'),
            'locale' => $this->option('locale'),
        ];

        foreach ($cfg as $key => $value) {
            if (null === $value) {
                unset($cfg[$key]);
            }
        }
        $driver = $trans->getDriver($cfg);
        $result = $driver->destroy();

        $total = 0;
        $this->info("Destroying translations from '{$cfg['driver']}' driver.");
        foreach ($result as $locale => $translations) {
            $count = count($translations);
            $total += $count;
            $this->warn("Removed {$count} translation(s) of '{$locale}' locale.");
        }
        $this->info("{$total} translation(s) deleted.");
        $this->info('Done.');
    }
}
