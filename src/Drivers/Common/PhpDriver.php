<?php

namespace Sluy\LaravelTranslation\Drivers\Common;

use Exception;
use Sluy\LaravelTranslation\Drivers\AbstractFileDriver;
use Throwable;

/**
 * PHP driver.
 *
 * @author Stefan Luy<sluy1283@gmail.com.
 */
class PhpDriver extends AbstractFileDriver
{
    public function save(array $data, $locale = null): array
    {
        return [];
    }

    public function destroy($locales = null): array
    {
        return [];
    }

    public function getExtension(): string
    {
        return 'php';
    }

    public function makeLoad(string $path): array
    {
        try {
            $translations = include $path;
            if (!is_array($translations)) {
                throw new Exception("{$path} doesnt returns a valid php array.");
            }

            return $translations;
        } catch (Throwable $th) {
            throw new Exception("Error while opening {$path} file : ".$th->getMessage());
        }
    }

    public function makeFileContent(array $data): string
    {
        return '<?php'.var_export($data, true);
    }
}
