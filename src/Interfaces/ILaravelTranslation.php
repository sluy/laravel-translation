<?php

namespace Sluy\LaravelTranslation\Interfaces;

/**
 * Common interface of Translation Service.
 *
 * @author Stefan Luy<sluy1283@gmail.com>
 */
interface ILaravelTranslation
{
    /**
     * Export translations from driver to another driver.
     *
     * @param array|Repository $cfg configuration of exportation
     *
     * @return array an array with stored locales -> translations
     */
    public function export($cfg): array;

    /**
     * Returns a new driver.
     *
     * @param array|\Illuminate\Support\Repository|string Driver name or driver config.
     *                                                    In Array/Repository cases, 'driver'
     *                                                    key must be mandatory. It will contains
     *                                                    the driver name.
     * @param mixed $var
     *
     * @throws \Exception if driver name isnt defined, cant resolve driver class or driver
     *                    class doesnt implements IDriver interface
     *
     * @return \Sluy\LaravelTranslation\Interfaces\IDriver
     */
    public function getDriver($var): IDriver;
}
