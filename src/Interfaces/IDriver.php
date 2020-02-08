<?php

namespace Sluy\LaravelTranslation\Interfaces;

use Closure;
use Illuminate\Config\Repository;

/**
 * Common interface of Translation Drivers.
 *
 * @author Stefan Luy<sluy1283@gmail.com>
 */
interface IDriver
{
    /**
     * Returns location of data.
     *
     * @return string
     */
    public function getLocation(): string;
    /**
     * Returns defined locales.
     *
     * @return array
     */
    public function getDefinedLocales(): array;
    /**
     * Store translation data.
     *
     * @param array  $data   translations to store
     * @param string $locale Associated locale of translations. If not defined it will lookup in
     *                       driver options.
     *
     * @return array an array with key as locale; their values are an array with
     *               stored translation keys
     */
    public function save(array $data, string $locale = null): array;

    /**
     * Removes stored data.
     *
     * @param array|string $locales Specific locale(s) to remove. If not defined it will returns
     *                              all locales. It will be an string with locales (separated
     *                              with comma) or an array with locales.
     *
     * @return array An array with key as locale. Their values are an array with
     *               removed translation keys.
     */
    public function destroy($locales = null): array;

    /**
     * Load and returns stored data of specific locale(s).
     *
     * @param array|string $locales Specific locale(s) to load. If not defined it will returns
     *                              all locales. It will be an string with locales (separated
     *                              with comma) or an array with locales.
     *
     * @return array an array with key as locale. Their values are an array with
     *               translation keys -> values.
     */
    public function load($locales = null): array;

    /**
     * Get's config instance of driver.
     *
     * @param array|\Illuminate\Config\Repository $values values to import
     */
    public function cfg($values = null): Repository;

    /**
     * Triggers an event dispatching all associated callbacks.
     *
     * @param string $event event name
     * @param array  $args  arguments to provide in callback's
     *
     * @return \Sluy\LaravelTranslation\Interfaces\IDriver
     */
    public function trigger(string $event, array $args = []);

    /**
     * Adds a new callback to specific event.
     *
     * @param string   $event    event name
     * @param \Closure $callback function to execute when event is triggered
     *
     * @return \Sluy\LaravelTranslation\Interfaces\IDriver
     */
    public function on(string $event, Closure $callback);
}
