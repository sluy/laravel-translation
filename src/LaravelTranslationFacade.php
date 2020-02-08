<?php

namespace Sluy\LaravelTranslation;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Sluy\LaravelTranslation\LaravelTranslation
 */
class LaravelTranslationFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-translation';
    }
}
