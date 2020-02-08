<?php

namespace Sluy\LaravelTranslation\Commands;

use Illuminate\Console\Command;
use Sluy\LaravelTranslation\LaravelTranslation;

/**
 * Artisan Command to import translations beetween formats.
 *
 * @author Stefan Luy<sluy1283@gmail.com>
 */
class ImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravel-translation:import {dst_driver} {src_driver}  {--locale=} {--src_location=} {--dst_location=}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import translations from formats. Reverse command of laravel-translation:export.';

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
            'dst.driver' => $this->argument('dst_driver'),
            'dst.location' => $this->option('dst_location'),
            'src.driver' => $this->argument('src_driver'),
            'src.location' => $this->option('src_location'),
            'locale' => $this->option('locale'),
        ];

        foreach ($cfg as $key => $value) {
            if (null === $value) {
                unset($cfg[$key]);
            }
        }
        // Exporting from php driver to js driver
        $this->warn(__('laravel-translation::commands.exporting_from_to', [
            'from' => $cfg['src.driver'],
        ]));

        $result = $trans->export($cfg);

        $this->info(__(
            'laravel-translation::commands.export_success',
            [
                'count' => count($result),
                'to' => $cfg['dst.driver']
            ]
        ));
    }
}
