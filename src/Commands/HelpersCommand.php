<?php

namespace Sluy\LaravelTranslation\Commands;

use Illuminate\Console\Command;
use Sluy\LaravelTranslation\LaravelTranslation;

/**
 * Artisan Command to add laravel-translation Helpers.
 *
 * @author Stefan Luy<sluy1283@gmail.com>
 */
class HelpersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravel-translation:helpers {name} {--bootstrap=} {--rewrite=}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add useful translation helpers like js, vuejs function and more.';

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
        $name = $this->argument('name');
        $bootstrap = $this->option('bootstrap') === 'true';
        $rewrite = $this->option('rewrite') === 'true';
        $res = null;
        switch ($name) {
            case 'js':
                $this->warn('Installing common js helper...');
                $res = $trans->installJsHelper($rewrite, $bootstrap);
                break;
            case 'vue':
                $this->warn('Installing vuejs extension...');
                $res = $trans->installVuejsExtension($rewrite, $bootstrap);
                break;
            default:
                $this->error("Unknow helper name argument. Available values can be 'js' or 'vue'");
                return;
        }
        $this->info("Installed in '{$res}.'");
    }
}
