<?php

namespace Sluy\LaravelTranslation\Models;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    protected $fillable = [
        'locale', 'key', 'value',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('laravel-translation.table_names.translations', 'translations'));
    }
}
