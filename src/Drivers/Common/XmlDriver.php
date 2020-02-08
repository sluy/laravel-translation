<?php

namespace Sluy\LaravelTranslation\Drivers\Common;

use Exception;
use Illuminate\Support\Arr;
use Sluy\LaravelTranslation\Drivers\AbstractFileDriver;
use Throwable;

/**
 * Xml driver.
 *
 * @author Stefan Luy<sluy1283@gmail.com.
 */
class XmlDriver extends AbstractFileDriver
{
    public function getExtension(): string
    {
        return 'xml';
    }

    public function makeLoad(string $path): array
    {
        try {
            $data = [];
            $raw = simplexml_load_string(html_entity_decode(file_get_contents($path)));
            foreach ($raw->item as $item) {
                $value = $item[0]->__toString();
                $key = $item[0]['key'][0]->__toString();
                if (!empty($key)) {
                    Arr::set($data, $key, $value);
                }
            }

            return $data;
        } catch (Throwable $th) {
            throw new Exception("Error while opening {$path} file : ".$th->getMessage());
        }
    }

    public function makeFileContent(array $data): string
    {
        $plain = Arr::dot($data);
        $content = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $content .= "\t<translations>\n";
        foreach ($plain as $key => $value) {
            if (is_array($value)) {
                $value = '';
            }
            $content .= "\t\t<item key=\"{$key}\">{$value}</item>\n";
        }
        $content .= "\t</translations>\n";
        return $content;
    }
}
