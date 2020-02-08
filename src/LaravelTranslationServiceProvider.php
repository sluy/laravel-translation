<?php

namespace Sluy\LaravelTranslation;

use Illuminate\Support\ServiceProvider;
use Sluy\LaravelTranslation\Commands\DestroyCommand;
use Sluy\LaravelTranslation\Commands\ImportCommand;
use Sluy\LaravelTranslation\Commands\ExportCommand;
use Sluy\LaravelTranslation\Commands\HelpersCommand;
use Sluy\LaravelTranslation\Commands\RoutesCommand;
use Sluy\LaravelTranslation\Commands\ViewsCommand;

/**
 * Translation service.
 * publishs all needed to perform all operations.
 */
class LaravelTranslationServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'laravel-translation');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-translation');
        if ($this->app->runningInConsole()) {
            $this->publishCommands();
            $this->publishConfig();
            $this->publishMigrations();
            $this->publishLang();
        } else {
            // Autodetect language from headers ?
            if (config('laravel-translation.autodetect_language') === true) {
                $this->app->get('laravel-translation')->setLanguageFromHttpHeaders();
            }
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laravel-translation');
        $this->app->singleton('laravel-translation', function () {
            return new LaravelTranslation(config('laravel-translation'));
        });
    }

    /**
     * Publish language translations for frontend.
     *
     * @return void
     */
    protected function publishLang() {
        $this->publishes([
            __DIR__ . '/../resources/lang' => resource_path('lang/vendor/laravel-translation'),
        ], 'lang');
    }

    /**
     * Publish configuration.
     *
     * @return void
     */
    protected function publishConfig()
    {
        $this->publishes([
            __DIR__.'/../config/config.php' => config_path('laravel-translation.php'),
        ], 'config');
    }
    /**
     * Publish Artisan Commands.
     *
     * @return void
     */
    protected function publishCommands()
    {
        $this->commands([
            HelpersCommand::class,
            RoutesCommand::class,
            ImportCommand::class,
            ExportCommand::class,
            DestroyCommand::class,
            ViewsCommand::class
        ]);
    }
    /**
     * Publish Migrations.
     *
     * @return void
     */
    protected function publishMigrations()
    {
        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'migrations');
    }
}
