<?php

namespace Sluy\LaravelTranslation;

use DirectoryIterator;
use Exception;
use Illuminate\Config\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Sluy\LaravelTranslation\Interfaces\IDriver;
use Sluy\LaravelTranslation\Interfaces\ILaravelTranslation;

/**
 * Main service to provide access and exportations between translation drivers.
 *
 * @author Stefan Luy<sluy1283@gmail.com>
 */
class LaravelTranslation implements ILaravelTranslation
{
    /**
     * Configuration of curren instance.
     *
     * @var \Illuminate\Config\Repository
     */
    protected $config;
    /**
     * Initializes curren instance.
     * @param array $config Configuration parameters.
     */
    public function __construct(array $config = null)
    {
        $this->config = new Repository();
    }

    /**
     * Sets resolves and sets current request locale from 'Accept-Language' locale.
     *
     * @return void
     */
    public function setLanguageFromHttpHeaders()
    {
        $locales = $this->getLanguageFromHttpHeaders();
        if (!empty($locales)) {
            Lang::setLocale($locales[0]);
        }
    }
    /**
     * Gets locales from 'Accept-Language' Header.
     * Validates if locales exists in `resources/lang/{locale}.
     *
     * @return array
     */
    public function getLanguageFromHttpHeaders()
    {
        $raw = request()->header('Accept-Language');
        if (!is_string($raw) || empty($raw)) {
            return;
        }
        $locales = [];

        foreach (explode(';', $raw) as $sec) {
            foreach (explode(',', $sec) as $current) {
                $current = strtolower(trim($current));
                $path = resource_path('lang/' . $current);
                if (!in_array($current, $locales) && file_exists($path) && is_dir($path) && is_readable($path)) {
                    $locales[] = $current;
                }
            }
        }
        return $locales;
    }

    /**
     * Returns js helper file location.
     *
     * @return string As default will returns 'resources/js/libs/laravel-translation'.
     */
    public function getJsHelperPath()
    {
        return $this->config->get('paths.js_helper', resource_path('js/libs/laravel-translation.js'));
    }
    /**
     * Returns vuejs extension file location.
     *
     * @return string As default will returns 'resources/js/libs/vue/laravel-translation.js'.
     */
    public function getVuejsExtensionPath()
    {
        return $this->config->get('paths.vuejs_extension', resource_path('js/libs/vue/laravel-translation.js'));
    }
    /**
     * Returns 'bootstrap.js' file location.
     *
     * @return string As default will returns 'resources/js/bootstrap.js'.
     */
    public function getLaravelBootstrapJsPath()
    {
        return $this->config->get('paths.laravel_bootstrap_js', resource_path('js/bootstrap.js'));
    }
    /**
     * Returns location of package views in laravel.
     *
     * @return string As default will returns 'resources/views/vendor/laravel-translation'.
     */
    public function getViewsPath()
    {
        return $this->config->get('paths.laravel_views', resource_path('views/vendor/laravel-translation'));
    }
    /**
     * Returns 'app.js' file location.
     *
     * @return string As default will returs 'resources/js/app.js".
     */
    public function getLaravelAppJsPath()
    {
        return $this->config->get('paths.laravel_app_js', resource_path('js/app.js'));
    }

    protected function copyRecursive(string $src, string $dst, $rewrite = false)
    {
        if (!file_exists($dst) || !is_writable($dst) || !is_dir($dst)) {
            throw new Exception("Cant write in destination folder {$dst}");
        }
        foreach (new DirectoryIterator($src) as $f) {
            if ($f->isDot()) {
                continue;
            }
            $srcFile = $src . '/' . $f->getFilename();
            $dstFile = $dst . '/' . $f->getFilename();

            if ($f->isDir()) {
                if (!file_exists($dstFile)) {
                    mkdir($dstFile);
                }
                $this->copyRecursive($srcFile, $dstFile, $rewrite);
                continue;
            }
            if (file_exists($dstFile)) {
                if (!$rewrite) {
                    continue;
                }
                unlink($dstFile);
            }
            copy($srcFile, $dstFile);
        }
    }

    /**
     * install frontend views.
     * @param string  $format  Format name. Atm : 'html' or 'bootstrap'.
     * @param boolean $rewrite Determines if will rewrite files.
     * @return string Path of views.
     */
    public function installViews($format, bool $rewrite = false)
    {
        $srcPath = dirname(__DIR__) . '/resources/views/' . $format;
        $dstPath = $this->getViewsPath();
        if (!file_exists($dstPath)) {
            mkdir($dstPath, 0775, true);
        }
        $this->copyRecursive($srcPath, $dstPath, $rewrite);
        Artisan::call('view:clear');
        return $dstPath;
    }

    /**
     * Install js helper.
     *
     * @param boolean $rewrite Determines if will rewrite file.
     * @param boolean $bootstrap  Determines if will install in boostrap.js file.
     *
     * @return string Path of installed file.
     */
    public function installJsHelper(bool $rewrite = false, bool $bootstrap = false)
    {
        $srcPath = dirname(__DIR__) . '/resources/templates/js_helper.js';
        $requiredPath = substr($this->relativePath(
            dirname($this->getLaravelBootstrapJsPath()),
            $this->getJsHelperPath()
        ), 0, -3);
        return $this->installJsResource(
            $srcPath,
            $this->getJsHelperPath(),
            [
                'js_collection_path' => $this->relativePath(
                    dirname($this->getJsHelperPath()),
                    $this->getDriver('js')->getLocation()
                ),
            ],
            $rewrite,
            $bootstrap,
            [
                '',
                '',
                '/**',
                ' * LaravelTranslation class/methods in window global.',
                ' */',
                'require("' . $requiredPath . '").default.injectGlobal();'
            ]
        );
    }



    /**
     * Install VueJS extension.
     *
     * @param boolean $rewrite Determines if will rewrite file.
     * @param boolean $bootstrap  Determines if will install in boostrap.js file.
     *
     * @return string Path of installed file.
     */
    public function installVuejsExtension(bool $rewrite = false, bool $bootstrap = false)
    {
        $srcPath = dirname(__DIR__) . '/resources/templates/vuejs_extension.js';
        $requiredPath = substr($this->relativePath(
            dirname($this->getLaravelBootstrapJsPath()),
            $this->getVuejsExtensionPath()
        ), 0, -3);
        return $this->installJsResource(
            $srcPath,
            $this->getVuejsExtensionPath(),
            [
                'translation_lib_path' => substr($this->relativePath(
                    dirname($this->getVuejsExtensionPath()),
                    $this->getJsHelperPath()
                ), 0, -3),
            ],
            $rewrite,
            $bootstrap,
            [
                '',
                '',
                '/**',
                ' * LaravelTranslation access from VueJS',
                ' */',
                'require("' . $requiredPath . '").default.inject();'
            ]
        );
    }

    /**
     * Install api routes.
     *
     * @param string $path Path to route file/dir. If $path are a directory, it will appends
     *                     'api.php' filename.
     *
     * @return string Path to modified route file.
     */
    public function installApiRoutes(string $path = null)
    {
        return $this->installRoutes('api', [
            '',
            '// Laravel Translations (https://github.com/sluy/laravel-translation)',
            '',
            "Route::namespace('\\Sluy\\LaravelTranslation\\Http\\Controllers\Api')->prefix('laravel-translation')->name('laravel-translation.api.')->group(function () {",
            "\tRoute::name('driver.')->prefix('drivers')->group(function () {",
            "\t\tRoute::get('/', 'DriverController@index')->name('index');",
            "\t\tRoute::get('/{driver}', 'DriverController@edit')->name('edit');",
            "\t\tRoute::put('/{driver}', 'DriverController@update')->name('update');",
            "\t\tRoute::delete('/{driver}/{locale?}', 'DriverController@destroy')->name('destroy');",
            "\t\tRoute::get('/{driver}/download', 'DriverController@download')->name('download');",
            "\t});",
            '});',
        ], $path);
    }
    /**
     * Install web routes.
     *
     * @param string $path Path to route file/dir. If $path are a directory, it will appends
     *                     'web.php' filename.
     *
     * @return string Path to modified route file.
     */
    public function installWebRoutes(string $path = null)
    {
        return $this->installRoutes('web', [
            '',
            '// Laravel Translations (https://github.com/sluy/laravel-translation)',
            '',
            "Route::namespace('\\Sluy\\LaravelTranslation\\Http\\Controllers')->prefix('laravel-translation')->name('laravel-translation.')->group(function () {",
            "\tRoute::name('driver.')->prefix('drivers')->group(function () {",
            "\t\tRoute::get('/', 'DriverController@index')->name('index');",
            "\t\tRoute::get('/{driver}', 'DriverController@edit')->name('edit');",
            "\t\tRoute::put('/{driver}', 'DriverController@update')->name('update');",
            "\t\tRoute::delete('/{driver}/{locale?}', 'DriverController@destroy')->name('destroy');",
            "\t\tRoute::get('/{driver}/download', 'DriverController@download')->name('download');",
            "\t});",
            '});',
        ], $path);
    }

    /**
     * Export translations from driver to another driver.
     *
     * @param array|Repository $cfg configuration of exportation
     *
     * @return array an array with stored locales -> translations
     */
    public function export($cfg): array
    {
        $cfg = $this->formatSrcDstCfg($cfg);

        $src = $this->getDriver($cfg->get('src'));
        $dst = $this->getDriver($cfg->get('dst'));

        $translations = $src->load($cfg->get('locale'));
        $stored = $dst->save($translations, $cfg->get('locale'));
        return $stored;
    }

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
     */
    public function getDriver($var): IDriver
    {
        // Driver name
        $name = null;
        // If repository, will convert to array
        if ($var instanceof Repository) {
            $var = $var->all();
        }
        //If is array, sets name from 'driver' key.
        if (is_array($var)) {
            $var = Arr::dot($var);
            if (isset($var['driver'])) {
                $name = $var['driver'];
                unset($var['driver']);
            }
        }
        //If is string, sets name from this
        elseif (is_string($var)) {
            $name = $var;
            $var = [];
        }
        //Invalid driver name (or empty)
        if (!is_string($name) || empty($name)) {
            throw new Exception('Cant resolve translation driver. Translation driver name must be an string.');
        }
        //Default config
        $cfg = config("laravel-translation.drivers.{$name}");
        if (!is_array($cfg)) {
            $cfg = [];
        }
        //Inject new config values
        foreach ($var as $key => $value) {
            Arr::set($cfg, $key, $value);
        }
        //If isnt defined 'class' , empty or invalid
        if (!isset($cfg['class']) || !is_string($cfg['class']) || empty($cfg['class'])) {
            throw new Exception("Cant resolve translation driver '{$name}'. Class key isnt defined or invalid.");
        }
        // Gets class name from config and remove key for it.
        $classname = $cfg['class'];
        unset($cfg['class']);
        // Initialize driver class
        $instance = new $classname();
        // If initialized class doesnt implements IDriver
        if (!($instance instanceof IDriver)) {
            throw new Exception("'{$name}' driver ({$classname}) must implements 'Sluy\\LaravelTranslation\\Interfaces\\IDriver' interface.");
        }
        // Inject config
        $instance->cfg($cfg);
        // All good.
        return $instance;
    }


    /**
     * Returns relative path from base directory to another resource location.
     *
     * @param string $base     Base path of relative path.
     * @param string $resource Pointed resource path.
     *
     * @return string
     */
    protected function relativePath($base, $resource)
    {
        $raw = func_get_args();
        foreach ($raw as $k => $v) {
            //Normalizes path to DIRECTORY_SEPARATOR.
            $raw[$k] = str_replace('\\', '/', $raw[$k]);
            $raw[$k] = str_replace('/', DIRECTORY_SEPARATOR, $raw[$k]);

            //Removes last / (if exists)
            while (1) {
                if (!Str::endsWith($raw[$k], DIRECTORY_SEPARATOR)) {
                    break;
                }
                $raw[$k] = substr($raw[$k], 0, -1);
            }
        }
        $base = $raw[0];
        $resource = $raw[1];
        $baseSec = explode(DIRECTORY_SEPARATOR, $base);
        $sec = explode(DIRECTORY_SEPARATOR, $resource);
        $paths = [];
        while (count($sec) > 0) {

            $paths[] = array_pop($sec);
            $start = implode(\DIRECTORY_SEPARATOR, $sec);
            if (strlen($start) === 0) {
                $start = DIRECTORY_SEPARATOR;
            }
            if ($base === $start) {
                //dir is inside current (./my/dir)
                return './' . implode('/', array_reverse($paths));
            } else if (Str::startsWith($base, $start)) {
                // dir is nested in parent (../../my/dir)
                $counter = count($baseSec) - count($sec);
                for ($n = 0; $n < $counter; $n++) {
                    $paths[] = '..';
                }
                return implode('/', array_reverse($paths));
            }
        }
    }


    protected function installRoutes(string $type, $content, string $path = null)
    {
        if (!$path) {
            $path = base_path('routes');
        }

        if (file_exists($path) && is_dir($path)) {
            $path .= DIRECTORY_SEPARATOR . $type . '.php';
        }

        if (!is_file($path) || !\is_writable($path)) {
            throw new Exception("Cant write routes in '{$path}'.");
        }

        if (is_array($content)) {
            $content = implode("\n", $content);
        }
        file_put_contents($path, $content, FILE_APPEND | LOCK_EX);
        Artisan::call('route:clear');
        return $path;
    }

    protected function installJsResource(string $srcPath, string $dstPath, array $replaces = null, bool $rewrite = false, bool $bootstrap = false, $bootstrapContent = null)
    {
        $content = file_get_contents($srcPath);
        //Adding replaces...
        if ($replaces) {
            foreach ($replaces as $key => $value) {
                while (1) {
                    $replace = '{' . $key . '}';
                    if (strpos($content, $replace) !== false) {
                        $content = str_replace($replace, $value, $content);
                    } else {
                        break;
                    }
                }
            }
        }

        $dstDir = dirname($dstPath);
        if (!file_exists($dstDir)) {
            mkdir($dstDir, 0775, true);
        }
        if (!file_exists($dstPath) || $rewrite) {
            file_put_contents($dstPath, $content);
        }
        if ($bootstrap) {
            if (is_array($bootstrapContent)) {
                $bootstrapContent = implode("\n", $bootstrapContent);
            }
            file_put_contents($this->getLaravelBootstrapJsPath(), $bootstrapContent, FILE_APPEND | LOCK_EX);
            exec('cd ' . base_path() .  ' && npm run dev');
        }
        return $dstPath;
    }


    /**
     * Formats config when raw value defines src and dst values.
     *
     * @param mixed $raw
     *
     * @throws \Exception if Raw value inst array or Repository instance
     */
    protected function formatSrcDstCfg($raw): Repository
    {
        if ($raw instanceof Repository) {
            $raw = $raw->all();
        }
        if (!is_array($raw)) {
            throw new Exception('Invalid configuration format. Must be an array or instanceof Illuminate\Config\Repository');
        }
        $raw = Arr::dot($raw);
        $arr = [];

        $cfg = [
            'src' => [],
            'dst' => [],
        ];

        foreach ($raw as $key => $value) {
            if (null === $value || (is_string($value) && empty($value))) {
                continue;
            }
            if (Str::startsWith($key, 'src.') || Str::startsWith($key, 'dst.')) {
                Arr::set($cfg[substr($key, 0, 3)], substr($key, 4), $value);
            } else {
                foreach (['src', 'dst'] as $current) {
                    Arr::set($cfg[$current], $key, $value);
                }
            }
        }

        return new Repository($cfg);
    }
}
