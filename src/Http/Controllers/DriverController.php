<?php

namespace Sluy\LaravelTranslation\Http\Controllers;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Sluy\LaravelTranslation\Drivers\AbstractFileDriver;
use Sluy\LaravelTranslation\Interfaces\IDriver;
use Sluy\LaravelTranslation\LaravelTranslation;
use ZipArchive;

class DriverController
{
    public function index(LaravelTranslation $trans)
    {
        $drivers = $this->resolveAllDrivers($trans);

        return view('laravel-translation::drivers.index', compact('drivers'));
    }

    public function edit(LaravelTranslation $trans, $driver)
    {
        $importable = $this->resolveImportableDrivers($trans, $driver);
        $all = $this->resolveAllDrivers($trans);
        $fileDrivers = $this->resolveAllDrivers($trans, function (IDriver $current) {
            return false === $current instanceof AbstractFileDriver;
        });

        return view('laravel-translation::drivers.edit', compact('driver', 'importable', 'all', 'fileDrivers'));
    }

    public function download(LaravelTranslation $trans, $driver) {
        $instance = $trans->getDriver($driver);

        $dst = storage_path(uniqid().'.zip');
        $src = $instance->cfg()->get('location');
        $zip = new ZipArchive();
        $zip->open($dst, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($src),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $name => $file) {
            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($src) + 1);
                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
            }
        }
        // Zip archive will be created only after closing object
        $zip->close();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"" . $driver . ".zip\"");
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: " . filesize($dst));
        ob_end_flush();
        @readfile($dst);
        unlink($dst);
        die();
    }

    public function update(LaravelTranslation $trans, $driver)
    {
        return 'upload' === request()->input('from')
            ? $this->updateFromUpload($trans, $driver)
            : $this->updateFromFilesystem($trans, $driver);
    }

    public function updateFromUpload(LaravelTranslation $trans, $driver)
    {
        $fileDrivers = $this->resolveAllDrivers($trans, function (IDriver $current) {
            return false === $current instanceof AbstractFileDriver;
        });
        $data = request()->all();
        if (request()->file('upload-file')) {
            $data['upload-file'] = request()->file('upload-file');
        }
        $rules = [
            'upload-source' => [
                'required',
                Rule::in(array_keys($fileDrivers)),
            ],
            'upload-file' => [
                'required',
                'file',
                'mimes:zip',
            ],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return redirect()->route('laravel-translation.driver.edit', ['driver' => $driver])
                ->withErrors($validator)
                ->withInput()
            ;
        }
        $path = storage_path('tmp'.DIRECTORY_SEPARATOR.uniqid());
        $zipPath = $path.DIRECTORY_SEPARATOR.'upload.zip';
        $extractPath = $path.DIRECTORY_SEPARATOR.'extract';
        mkdir($extractPath, 0777, true);

        $data['upload-file']->move($path, 'upload.zip');

        $zip = new ZipArchive();
        $res = $zip->open($zipPath);
        if (true === $res) {
            $zip->extractTo($extractPath);
            $zip->close();
        }
        $opt = [
            'src.driver' => $data['upload-source'],
            'dst.driver' => $driver,
            'src.location' => $extractPath,
        ];
        $result = $trans->export($opt);


        $status = [
            'type' => 'save',
            'count' => count($result)
        ];

        return  redirect()
            ->route('laravel-translation.driver.index')
            ->with('status', $status)
            ->with('driver', $driver)
        ;
    }

    public function updateFromFilesystem(LaravelTranslation $trans, $driver)
    {
        $data = request()->all();

        $importable = $this->resolveImportableDrivers($trans, $driver);
        $availableLocales = [];
        if (isset($data['filesystem-source'], $importable[$data['filesystem-source']])) {
            $availableLocales = array_keys($importable[$data['filesystem-source']]);
        }
        $rules = [
            'filesystem-source' => [
                'required',
                Rule::in(array_keys($importable)),
            ],
            'filesystem-locales' => 'required',
            'filesystem-locales.*' => [
                Rule::in($availableLocales),
            ],
        ];
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return redirect()->route('laravel-translation.driver.edit', ['driver' => $driver])
                ->withErrors($validator)
                ->withInput()
            ;
        }
        $result = $trans->export([
            'src.driver' => $data['filesystem-source'],
            'dst.driver' => $driver,
            'locale' => implode(',', $data['filesystem-locales']),
        ]);
        
        $status = [
            'type' => 'save',
            'count' => count($result)
        ];


        return  redirect()
            ->route('laravel-translation.driver.index')
            ->with('status', $status)
            ->with('driver', $driver)
        ;
    }

    public function destroy(LaravelTranslation $trans, $driver, $locale = null)
    {
        $result = $trans->getDriver($driver)->destroy($locale);

        $status = [
            'type' => 'destroy',
            'count' => count($result)
        ];

        return redirect()
            ->route('laravel-translation.driver.index')
            ->with('status', $status)
            ->with('driver', $driver)
        ;
    }

    protected function resolveAllDrivers(LaravelTranslation $trans, $exclude = null)
    {
        $callback = null;

        if ($exclude instanceof Closure) {
            $callback = $exclude;
        } else {
            if (is_string($exclude)) {
                $exclude = [$exclude];
            }
            if (!is_array($exclude)) {
                $exclude = [];
            }
            $callback = function (IDriver $driver, string $name) use ($exclude) {
                return in_array($name, $exclude);
            };
        }

        $drivers = config('laravel-translation.drivers');
        if (!is_array($drivers)) {
            $drivers = [];
        }
        ksort($drivers);
        foreach ($drivers as $name => $config) {
            $driver = $trans->getDriver($name);
            if (true === $callback($driver, $name, $config)) {
                unset($drivers[$name]);

                continue;
            }

            $data = $driver->load();
            $locales = [];
            $count = 0;

            foreach ($data['common'] as $locale => $groups) {
                $locales[$locale] = count(Arr::dot($groups));
                $count += $locales[$locale];
            }

            foreach ($data['vendor'] as $package => $tmp) {
                foreach($tmp as $locale => $groups) {
                    if (!isset($locales[$locale])) {
                        $locales[$locale] = 0;
                    }
                    $currentCount = count(Arr::dot($groups));
                    $locales[$locale] += $currentCount;
                    $count += $currentCount;
                }
            }
            $drivers[$name]['file'] = $driver instanceof AbstractFileDriver;
            $drivers[$name]['locales'] = $locales;
            $drivers[$name]['translation_count'] = $count;
        }

        return $drivers;
    }

    protected function resolveImportableDrivers(LaravelTranslation $trans, $driver)
    {
        $importable = [];
        $drivers = config('laravel-translation.drivers');
        if (!is_array($drivers)) {
            $drivers = [];
        }

        foreach (array_keys($drivers) as $current) {
            if ($current === $driver) {
                continue;
            }
            $locales = [];
            $data = $trans->getDriver($current)->load();

            foreach ($data['common'] as $locale => $translations) {
                $translations = Arr::dot($translations);
                if (!empty($translations)) {
                    $locales[$locale] = count($translations);
                }
            }
            foreach ($data['vendor'] as $package => $tmp) {
                foreach ($tmp as $locale => $translations) {
                    $translations = Arr::dot($translations);
                    if(!empty($translations)) {
                        if(!isset($locales[$locale])) {
                            $locales[$locale] = 0;
                        }
                        $locales[$locale] += count($translations);
                    }

                }
            }
            if (!empty($locales)) {
                $importable[$current] = $locales;
            }
        }

        return $importable;
    }
}
