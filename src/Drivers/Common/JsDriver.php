<?php

namespace Sluy\LaravelTranslation\Drivers\Common;

use Exception;
use Illuminate\Support\Str;
use Sluy\LaravelTranslation\Drivers\AbstractFileDriver;
use Throwable;

/**
 * Javascript driver.
 *
 * @author Stefan Luy<sluy1283@gmail.com.
 */
class JsDriver extends AbstractFileDriver
{
    public function getExtension(): string
    {
        return 'js';
    }

    public function makeLoad(string $path): array
    {
        if (Str::endsWith($path, 'index.js')) {
            return [];
        }

        try {
            $raw = file_get_contents($path);
            if (strlen($raw) > 15) {
                $raw = substr($raw, 15);
            }
            if (Str::endsWith($raw, ';')) {
                $raw = substr($raw, 0, -1);
            }
            $translations = json_decode($raw, true);
            if (!is_array($translations)) {
                throw new Exception("{$path} doesnt returns a valid js object.");
            }

            return $translations;
        } catch (Throwable $th) {
            throw new Exception("Error while opening {$path} file : ".$th->getMessage());
        }
    }

    public function makeFileContent(array $data): string
    {
        return 'export default '.json_encode($data, JSON_PRETTY_PRINT).';';
    }

    public function npmReload($force = false)
    {
        if (true === $force || true === $this->cfg()->get('npm_reload')) {
            exec('cd '.$this->cfg()->get('npm_location', base_path()).' && npm run dev');
        }
    }

    protected function afterSave(array $locales, array $data)
    {
        $this->generateIndexes($data);
        $this->npmReload();
    }

    protected function afterDestroy()
    {
        $this->generateIndexes($this->load());
        $this->npmReload();
    }


    protected function generateIndexes (array $data, $force = false) {
        if  (empty($data) || (false === $force && false === $this->cfg()->get('generate_index'))) {
            return;
        }   
        if (empty($data)) {
            return;
        }
        $imports = [ ];
        $exports = [ 
            'common' => [ ],
            'vendor' => [ ],
        ];
        $path = $this->getPath("index.js");
        $content = "{}\n";
        if (isset($data['common']) && is_array($data['common']) && !empty($data['common'])) {
            foreach ($data['common'] as $locale => $locales) {
                $localeImports = [];
                $localeExports = [];
                $localeKey = "_common_locale_" . Str::camel($locale);
                $localePath = $this->getPath("{$locale}/index.js");
                $localeContent = "export default {};\n";

                foreach ($locales as $group => $translations) {
                    $groupKey = "_common_locale_group_" . Str::camel($group);
                    if (is_array($translations) && !empty($translations)) {
                        $localeImports[] = "import {$groupKey} from './$group';\n";
                        $localeExports[] = "\t'{$group}': {$groupKey}";
                    }
                }
                if (!empty($localeImports)) {
                    $localeContent = implode('', $localeImports) . "\n" .
                        "export default {\n" . implode(",\n", $localeExports) . "\n};\n";
                }
                file_put_contents($localePath, $localeContent);
                $imports[] = "import {$localeKey} from './$locale';\n";
                $exports['common'][] = "\t\t'{$locale}': {$localeKey}";
            }
        }
        if (isset($data['vendor']) && is_array($data['vendor']) && !empty($data['vendor'])) {
            foreach ($data['vendor'] as $package => $tmp) {
                //vendor/{package}/index.js
                $packageImports = [];
                $packageExports = [];
                $packageKey = "_vendor_" . Str::camel($package);
                $packagePath = $this->getPath("vendor/{$package}/index.js");
                $packageContent = "export default {};\n";
                foreach ($tmp as $locale => $locales) {
                    //vendor/{package}/{locale}/index.js
                    $localeImports = [];
                    $localeExports = [];
                    $localeKey = "_vendor_locale_" . Str::camel($locale);
                    $localePath = $this->getPath("vendor/{$package}/{$locale}/index.js");
                    $localeContent = "export default {};\n";

                    foreach ($locales as $group => $translations) {
                        $groupKey = "_vendor_locale_group_" . Str::camel($group);
                        if (is_array($translations) && !empty($translations)) {
                            $localeImports[] = "import {$groupKey} from './$group';\n";
                            $localeExports[] = "\t'{$group}': {$groupKey}";
                        }
                    }
                    if (!empty($localeImports)) {
                        $localeContent = implode('', $localeImports) . "\n" .
                            "export default {\n" . implode(",\n", $localeExports) . "\n};\n";
                    }
                    file_put_contents($localePath, $localeContent);
                    $packageImports[] = "import {$localeKey} from './$locale';\n";
                    $packageExports[] = "\t'{$locale}': {$localeKey}";
                }
                if (!empty($packageImports)) {
                    $packageContent = implode('', $packageImports) . "\n" .
                        "export default {\n" . implode(",\n", $packageExports) . "\n};\n";
                }
                file_put_contents($packagePath, $packageContent);
                $imports[] = "import {$packageKey} from './vendor/$package';\n";
                $exports['vendor'][] = "\t\t'{$package}': {$packageKey}";
            }
        }
        //writing main index.js
        if(!empty($imports)) {
            $content = implode('', $imports) . ";\n";
            $toExport = []; 
            foreach($exports as $type => $current) {
                if (!empty($current)) {
                    $toExport[] = "\t'{$type}': {\n" . 
                        implode(", \n", $current) . "\n\t}\n";
                }
            }
            $content .= "export default {\n" . implode(",\n" ,$toExport) . "\n};\n";
        }
        file_put_contents($path, $content);
    }
}
