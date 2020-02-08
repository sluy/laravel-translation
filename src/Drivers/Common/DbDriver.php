<?php

namespace Sluy\LaravelTranslation\Drivers\Common;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Sluy\LaravelTranslation\Drivers\AbstractDriver;

/**
 * Database driver.
 *
 * @author Stefan Luy<sluy1283@gmail.com>.
 */
class DbDriver extends AbstractDriver
{
    public function save(array $data, $locales = null): array
    {
        $this->validateDatabaseAccess();
        $locales = $this->normalizeLocales($locales);

        $saved = [];

        if (isset($data['common']) && is_array($data['common'])) {
            foreach($data['common'] as $locale => $tmp) {
                if (!empty($locales) && !in_array($locale, $locales)) {
                    continue;
                }
                $this->getQuery()->where('locale', $locale)->where('package', null)->delete();
                foreach ($tmp as $group => $raw) {
                    $translations = Arr::dot($raw);
                    foreach ($translations as $key => $value) {
                        if (is_array($value)) {
                            $value = '';
                        }
                        $this->getQuery()->insert([
                            'package' => null,
                            'locale' => $locale,
                            'group' => $group,
                            'key' => $key,
                            'value' => $value,
                        ]);
                    }
                    $saved = array_merge($saved, Arr::dot($raw, "common.{$locale}.{$group}."));
                }
            }
        }
        if (isset($data['vendor']) && is_array($data['vendor'])) {
            foreach($data['vendor'] as $package => $d) {
                foreach ($d as $locale => $tmp) {
                    if (!empty($locales) && !in_array($locale, $locales)) {
                        continue;
                    }
                    $this->getQuery()->where('locale', $locale)->where('package', $package)->delete();
                    foreach ($tmp as $group => $raw) {
                        $translations = Arr::dot($raw);
                        foreach ($translations as $key => $value) {
                            if (is_array($value)) {
                                $value = '';
                            }
                            $this->getQuery()->insert([
                                'package' => $package,
                                'locale' => $locale,
                                'group' => $group,
                                'key' => $key,
                                'value' => $value,
                            ]);
                        }
                        $saved = array_merge($saved, Arr::dot($raw, "vendor.{$package}.{$locale}.{$group}."));
                    }
                }

            }
        }
        $this->trigger('after_save', [$locale, $data, $saved]);
        return $saved;
    }

    public function destroy($locales = null): array
    {
        $locales = $this->normalizeLocales($locales);
        $deleted = [ ];
        $records = empty($locales)
            ? $this->getQuery()->get()
            : $this->getQuery()->whereIn('locale', $locales)->get();
        
        foreach ($records as $record) {
            $this->getQuery()->where('id', $record->id)->delete();
            $vendor = empty($record->package) ? 'common.' : "vendor.{$record->package}";
            $key = "{$vendor}.{$record->locale}.{$record->group}.{$record->key}";
            $deleted[$key] = $record->value;
        }

        $this->trigger('after_destroy', [$locales, $deleted]);
        return $deleted;
    }


    public function getDefinedLocales (): array {
        return $this->getQuery()
                    ->select('locale')
                    ->distinct()
                    ->orderBy('locale', 'ASC')
                    ->pluck('locale')
                    ->toArray();

    }

    public function load($locales = null): array
    {
        $this->validateDatabaseAccess();
        $locales = $this->normalizeLocales($locales);

        $translations = [];

        $records = empty($locales)
            ? $this->getQuery()->get()
            : $this->getQuery()->whereIn('locale', $locales)->get();

        foreach ($records as $record) {
            $vendor = empty($record->package) ? 'common.' : "vendor.{$record->package}.";
            $key = "{$vendor}{$record->locale}.{$record->group}.{$record->key}";
            Arr::set($translations, $key, $record->value);
        }
        foreach(['vendor', 'common'] as $type) {
            if (!isset($translations[$type])) {
                $translations[$type] = [ ];
            }
        }
        return $this->sortKeys($translations);
    }

    public function getLocation (): string {
        return $this->cfg()->get('location', 'translations');
    }


    /**
     * Returns a DB query using connection & table defined in configuration.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getQuery()
    {
        return DB::connection($this->cfg()->get('connection'))->table($this->cfg()->get('location'));
    }

    protected function validateDatabaseAccess()
    {
        $conn = $this->cfg()->get('connection');
        $table = $this->getLocation();
        $status = null === $conn
            ? Schema::hasTable($table)
            : Schema::connection($conn)->hasTable($table);
        if (!$status) {
            throw new Exception('Cant access to database table. Maybe forgot run migrations?');
        }
    }
}
