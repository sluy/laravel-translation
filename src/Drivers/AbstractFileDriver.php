<?php

namespace Sluy\LaravelTranslation\Drivers;

use DirectoryIterator;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Base file driver structure.
 *
 * @author Stefan Luy<sluy1283@gmail.com.
 */
abstract class AbstractFileDriver extends AbstractDriver
{

    public function getLocation(): string
    {
        return $this->cfg()->get('location', storage_path('laravel_translation/lang/' . $this->getName()));
    }

    public function load($locales = null): array
    {
        $ext = method_exists($this, 'getExtension')
            ? call_user_func([$this, 'getExtension'])
            : null;

        if (!is_string($ext) || empty($ext)) {
            throw new Exception(get_class($this) . '::getExtension() must returns an string with file extension.');
        }
        $locales = $this->normalizeLocales($locales);
        $raw = [
            'common' => [],
            'vendor' => []
        ];
        if (empty($locales)) {
            $locales = $this->getDefinedLocales();
            return empty($locales)
                ? $raw
                : $this->load($locales);
        }
        // Iterate locales
        foreach ($locales as $locale) {
            $raw['common'][$locale] = [];
            $this->validateDirectoryAccess($locale);
            $path = $this->getPath($locale);
            // Iterate files in folder
            foreach (new DirectoryIterator($path) as $f) {
                if ($f->isDot() || !$f->isFile() || $ext !== $f->getExtension()) {
                    continue;
                }
                $this->validateFileAccess($f->getRealPath());
                $filePath = $f->getRealPath();
                $group = substr($f->getFilename(), 0, - (strlen($ext) + 1));
                // Caling Driver makeLoad to get translations of group;
                $tmp = $this->makeLoad($filePath);
                if (!empty($tmp)) {
                    $raw['common'][$locale][$group] = $this->makeLoad($filePath);
                }
            }
        }
        $path = $this->getPath('vendor');

        //Iterate vendors
        if (file_exists($path) && is_dir($path)) {
            $this->validateDirectoryAccess($path);
            //iterating vendors/
            foreach (new DirectoryIterator($path) as $p) {
                if ($p->isDot() || !$p->isDir()) {
                    continue;
                }
                $this->validateDirectoryAccess($p->getRealPath());
                $vendor = $p->getFilename();
                //iterating vendors/{vendor}
                foreach(new DirectoryIterator($p->getRealPath()) as $l) {
                    if ($l->isDot() || !$l->isDir() || !in_array($l->getFilename(), $locales)) {
                        continue;
                    }
                    $this->validateDirectoryAccess($l->getRealPath());
                    $locale = $l->getFilename();
                    //iterating vendors/{vendor}/{locale}
                    foreach(new DirectoryIterator($l->getRealPath()) as $f) {
                        if ($f->isDot() || !$f->isFile() || $ext !== $f->getExtension()) {
                            continue;
                        }
                        $this->validateFileAccess($f->getRealPath());
                        $filePath = $f->getRealPath();
                        $group = substr($f->getFilename(), 0, - (strlen($ext) + 1));
                        // Caling Driver makeLoad to get translations of group;
                        $tmp = $this->makeLoad($filePath);
                        if (!empty($tmp)) {
                            $raw['vendor'][$vendor][$locale][$group] = $this->makeLoad($filePath);
                        }
                    }
                }
            }
        }
        // Beautified translations (sorted keys).
        return $this->sortKeys($raw);
    }

    public function save(array $data, $locales = null): array
    {
        $locales = $this->normalizeLocales($locales);


        $ext = method_exists($this, 'getExtension')
            ? call_user_func([$this, 'getExtension'])
            : null;

        if (!is_string($ext) || empty($ext)) {
            throw new Exception(get_class($this).'::getExtension() must returns an string with file extension.');
        }
        $saved = [];
        $data = $this->sortKeys($data);
        $this->validateDirectoryAccess();
        

        if (isset($data['common']) && is_array($data['common'])) {
            $this->saveCommon($data['common'], $locales, $saved);    
        }
        // Vendor
        if (isset($data['vendor']) && is_array($data['vendor'])) {
            $this->saveVendor($data['vendor'], $locales, $saved);
        }

        $multi = $this->sortKeys($saved);
        
        $this->trigger('after_save', [$locales, $multi, $saved]);
        return $saved;
    }

    public function destroy($locales = null): array
    {
        $old = $this->load($locales);
        foreach ($old['common'] as $locale => $translations) {
            File::deleteDirectory($this->getPath($locale));
        }
        foreach($old['vendor'] as $vendor => $tmp) {
            $vendorPath = $this->getPath("vendor/{$vendor}");
            if (file_exists($vendorPath)) {
                $this->validateDirectoryAccess($vendorPath);
                foreach ($tmp as $locale => $translations) {
                    $path = $this->getPath("vendor/{$vendor}/{$locale}");
                    if (file_exists($vendor)) {
                        $this->validateDirectoryAccess($path);
                        File::deleteDirectory($path);
                    }
                }
            }
            //if vendor is empty, destroy it.
            if (count(scandir($path)) == 2) {
                File::deleteDirectory($path);
            }
        }
        $deleted = Arr::dot($old);
        $this->trigger('after_destroy', [$locales, $deleted]);
        return $deleted;
    }

    /**
     * Returns default extension of translation 'group' files.
     */
    abstract public function getExtension(): string;

    /**
     * Makes a load of some locale 'group' in filesystem.
     *
     * @param string $path absolute path of file
     *
     * @return array translation key's -> value's
     */
    abstract public function makeLoad(string $path): array;

    /**
     * Makes a file content from an array of translations.
     *
     * @param array $data translation key's -> value's
     *
     * @return string formatted file content
     */
    abstract public function makeFileContent(array $data): string;


    public function getDefinedLocales(): array
    {
        $this->validateDirectoryAccess();
        $path = $this->getPath();
        $locales = [ ];
        foreach (new DirectoryIterator($path) as $f) {
            if (!$f->isDot() && $f->isDir() && $f->getFilename() !== 'vendor') {
                $locales[] = $f->getFilename();
            }
        }
        $path = $this->getPath('vendor');
        if (file_exists($path) && !is_dir($path)) {
            $this->validateDirectoryAccess('vendor');
            foreach (new DirectoryIterator($path) as $f) {
                if (!$f->isDot() || !$f->isDir()) {
                    continue;
                }
                $this->validateDirectoryAccess($f->getRealPath());
                foreach (new DirectoryIterator($f->getRealPath) as $l) {
                    if (!$l->isDot() && $l->isDir() && !in_array($l->getFilename(), $locales)) {
                        $locales[] = $l->getFilename();
                    }
                }
            }
        }
        return $locales;
    }



    protected function saveCommon(array $data, array $locales, array &$saved = null): array
    {
        if (!is_array($saved)) {
            $saved = [];
        }
        $this->validateDirectoryAccess();
        foreach ($data as $locale => $groups) {
            if (!empty($locales) && !in_array($locale, $locales)) {
                continue;
            }
            $localePath = $this->getPath($locale);
            if (file_exists($localePath)) {
                $this->validateDirectoryAccess($localePath);
                File::deleteDirectory($localePath);
            }
            if (!is_array($groups) || empty($groups)) {
                continue;
            }
            mkdir($localePath);
            foreach ($groups as $group => $translations) {
                $filename = $group . '.' . $this->getExtension();
                $filepath = $this->getPath("{$locale}/{$filename}");
                if (file_exists($filepath)) {
                    $this->validateFileAccess($filepath);
                }
                file_put_contents($filepath, $this->makeFileContent($translations));
                $saved = array_merge($saved, Arr::dot($translations, "common.{$locale}.{$group}."));
            }
        }
        return $saved;
    }


    protected function saveVendor(array $data, array $locales, array &$saved = null)
    {
        if (!is_array($saved)) {
            $saved = [];
        }
        $this->validateDirectoryAccess();
        $path = $this->getPath('vendor');
        // Create "vendor" Path
        if (!file_exists($path)) {
            mkdir($path);
        }
        $this->validateDirectoryAccess($path);
        foreach ($data as $vendor => $tmp) {
            if (!is_array($tmp) || empty($tmp)) {
                continue;
            }
            $vendorPath = $this->getPath('vendor/' . $vendor);
            if (!file_exists($vendorPath)) {
                mkdir($vendorPath);
            }
            $this->validateDirectoryAccess($vendorPath);
            // pointing "vendor/{vendor}" path
            foreach ($tmp as $locale => $groups) {
                if((!empty($locales) && !in_array($locale, $locales))) {
                    continue;
                }
                $localePath = $this->getPath("vendor/{$vendor}/$locale"); 
                if (file_exists($localePath)) {
                    $this->validateDirectoryAccess($localePath);
                    File::deleteDirectory($localePath);
                }
                if(!is_array($groups) || empty($groups)) {
                    continue;
                }
                mkdir($localePath);
                //pointing over "vendor/{vendor}/{locale}"
                foreach ($groups as $group => $translations) {
                    $filename = $group . '.' . $this->getExtension();
                    $filepath = $this->getPath("vendor/{$vendor}/{$locale}/{$filename}");
                    if (file_exists($filepath)) {
                        $this->validateFileAccess($filepath);
                    }
                    file_put_contents($filepath, $this->makeFileContent($translations));
                    $pre = "vendor.{$vendor}.{$locale}.{$group}.";
                    $saved = array_merge($saved, Arr::dot($translations, $pre));
                }
            }
            
        }
    }

    /**
     * Validates read/write access to file.
     *
     * @param string $path path to validate
     *
     * @throws \Exception if file doesnt exists, isnt a file or can read/write
     */
    protected function validateFileAccess(string $path = null)
    {
        return $this->validateAccess($path, false);
    }

    /**
     * Validates read/write access to directory.
     *
     * @param string $path path to validate
     *
     * @throws \Exception if directory doesnt exists, isnt a directory or can read/write
     */
    protected function validateDirectoryAccess(string $path = null)
    {
        return $this->validateAccess($path, true);
    }

    /**
     * Validates read/write access to resources in filesystem.
     *
     * @param string $path path to validate
     *
     * @throws \Exception if resource doesnt exists, isnt a file/directory or can read/write
     */
    protected function validateAccess(string $path = null, bool $isDir = false)
    {
        $path = $this->getPath($path);

        if (!file_exists($path)) {
            if ($isDir) {
                try {
                    mkdir($path, 0775, true);
                } catch (Throwable $th) {
                    throw new Exception("Cant access to directory '{$path}' (doesnt exists)");
                }
            } else {
                throw new Exception("Cant access to file '{$path}' (doesnt exists)");
            }
        }
        if ($isDir && !is_dir($path)) {
            throw new Exception("{$path} must be an valid directory.");
        }
        if (!$isDir && !is_file($path)) {
            throw new Exception("'{$path}' must be an valid file");
        }
        if (!is_readable($path)) {
            throw new Exception("{$path} must be readable.");
        }
        if (!is_writable($path)) {
            throw new Exception("{$path} must be writable.");
        }
    }

    /**
     * Returns working location in filesystem.
     *
     * @param string $path relative path inside working location
     *
     * @throws \Exception if 'location' option inst defined in config or isnt a valid path
     *
     * @return string
     */
    protected function getPath(string $path = null)
    {
        if ($this->isAbsolutePath($path)) {
            return $path;
        }
        $loc = $this->getLocation();
        if (!is_string($loc) || empty($loc)) {
            throw new Exception("Cant resolve location folder of '".get_class($this)."' driver.");
        }

        return $loc.(empty($path) ? '' : (DIRECTORY_SEPARATOR.$path));
    }


    protected function isAbsolutePath (string $path = null) {
        if (empty($path)) {
            return false;
        }
        $path = str_replace('\\', '/', $path);
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (Str::startsWith(strtoupper(PHP_OS), 'win')) {
            return strlen($path) > 2 && 
                $path[1] . $path[2] === ':' . DIRECTORY_SEPARATOR;
        }
        return $path[0] === DIRECTORY_SEPARATOR;
    }
}
