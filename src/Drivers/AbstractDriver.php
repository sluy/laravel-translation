<?php

namespace Sluy\LaravelTranslation\Drivers;

use Closure;
use Exception;
use Illuminate\Config\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Sluy\LaravelTranslation\Interfaces\IDriver;

/**
 * Base driver structure.
 *
 * @author Stefan Luy<sluy1283@gmail.com.
 */
abstract class AbstractDriver implements IDriver
{
    protected $_cfg;

    protected $_events = [];

    public function getName() {
        return substr(Str::snake(get_class($this)),0, -7);
    }


    public function trigger(string $event, array $args = [])
    {
        $callbacks = [];

        $method = Str::camel($event);

        if (method_exists($this, $method)) {
            $callbacks[] = [$this, $method];
        }

        $configCallbacks = $this->cfg()->get('events.'.$event);

        if (is_array($configCallbacks)) {
            $callbacks = array_merge($callbacks, $configCallbacks);
        }

        if (isset($this->_events[$event])) {
            $callbacks = array_merge($callbacks, $this->_events[$event]);
        }

        $args[] = $this;

        foreach ($callbacks as $callback) {
            call_user_func_array($callback, $args);
        }
    }

    public function on(string $event, Closure $callback)
    {
        if (!isset($this->_events[$event])) {
            $this->_events[$event] = [];
        }
        $this->_events[$event][] = $callback;
    }

    public function cfg($values = null): Repository
    {
        if (!$this->_cfg) {
            $this->_cfg = new Repository();
        }
        foreach (func_get_args() as $current) {
            if (null !== $current) {
                if ($current instanceof Repository) {
                    $current = $current->all();
                }
                if (is_array($current)) {
                    foreach ($current as $key => $value) {
                        $this->_cfg->set($key, $value);
                    }
                } else {
                    throw new Exception("Import values must be an array or  an instance of 'Illuminate\\Config\\Repository'.");
                }
            }
        }

        return $this->_cfg;
    }

    abstract public function save(array $data, string $locale = null): array;

    abstract public function destroy($locales = null): array;

    abstract public function load($locales = null): array;

    abstract public function getLocation(): string;

    abstract public function getDefinedLocales(): array;

    /**
     * Returns defined locales mergin provided data with config data.
     *
     * @param null|array|string $locales a list with locales in string (separated with comma) or
     *                                   array
     */
    protected function normalizeLocales($locales = null): array
    {
        $arr = [];
        $test = func_get_args();
        $test[] = $this->cfg()->get('locale');

        foreach ($test as $current) {
            if (is_string($current) && !empty($current)) {
                if (false === strpos($current, ',')) {
                    if (!in_array($current, $arr)) {
                        $arr[] = $current;
                    }

                    continue;
                }
                $current = explode(',', $current);
            }
            if (is_array($current)) {
                foreach (array_map('trim', $current) as $tmp) {
                    if (!empty($tmp) && !in_array($tmp, $arr)) {
                        $arr[] = $tmp;
                    }
                }
            }
        }

        return $arr;
    }

    /**
     * Sort array keys recursivelly. To beautify translation arrays.
     *
     * @param array $raw raw array
     *
     * @return array
     */
    protected function sortKeys(array $raw)
    {
        $raw = Arr::dot($raw);
        $data = [];
        ksort($raw);
        foreach ($raw as $key => $value) {
            Arr::set($data, $key, $value);
        }

        return $data;
    }
}
