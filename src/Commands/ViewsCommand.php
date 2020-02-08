<?php

namespace Sluy\LaravelTranslation\Commands;

use Illuminate\Console\Command;
use Sluy\LaravelTranslation\LaravelTranslation;

/**
 * Artisan Command to add laravel-translation Helpers.
 *
 * @author Stefan Luy<sluy1283@gmail.com>
 */
class ViewsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravel-translation:views {format} {--rewrite=}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add GUI Administration views for different sources.';

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
        $format = $this->argument('format');
        $rewrite = $this->option('rewrite') === 'true';
        if (empty($format)) {
            $format = 'html';
        }
        $valids = ['html', 'bootstrap'];
        $ui = ['bootstrap', 'vue', 'react'];

        if (!in_array($format, $valids)) {
            $this->error('Invalid format.');
            $this->error("Format must be any of these values: '" . implode("'", $valids) . "'");
            return;
        }
        if (in_array($format, $ui)) {
            $this->warn('*****************************');
            $this->warn("* '{$format}' views needs 'laravel/ui' package");
            $this->warn('* If you havent installed yet, do:');
            $this->warn('* composer require laravel/ui');
            $this->warn('* php artisan ui ' . $format);
            $this->warn('*****************************');
        }
        $res = $trans->installViews($format, $rewrite);
        $this->info("{$format} views installed in '{$res}'");

    }
}
