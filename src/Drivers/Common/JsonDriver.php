<?php

namespace Sluy\LaravelTranslation\Drivers\Common;

use Exception;
use Sluy\LaravelTranslation\Drivers\AbstractFileDriver;
use Throwable;

/**
 * JSON Driver.
 *
 * @author Stefan Luy<sluy1283@gmail.com.
 */
class JsonDriver extends AbstractFileDriver
{
    public function getExtension(): string
    {
        return 'json';
    }

    public function makeLoad(string $path): array
    {
        try {
            $raw = file_get_contents($path);
            $translations = json_decode($raw, true);
            if (!is_array($translations)) {
                throw new Exception("{$path} doesnt returns a valid json.");
            }

            return $translations;
        } catch (Throwable $th) {
            throw new Exception("Error while opening {$path} file : ".$th->getMessage());
        }
    }

    public function makeFileContent(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }
}
