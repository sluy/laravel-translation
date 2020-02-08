<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['web']], function () {
    Route::get('laravel-translation/drivers', 'Sluy\LaravelTranslation\Http\Controllers\DriverController@index')->name('laravel-translation.driver.index');
    Route::get('laravel-translation/drivers/{driver}', 'Sluy\LaravelTranslation\Http\Controllers\DriverController@edit')->name('laravel-translation.driver.edit');
    Route::put('laravel-translation/drivers/{driver}', 'Sluy\LaravelTranslation\Http\Controllers\DriverController@update')->name('laravel-translation.driver.update');
    Route::delete('laravel-translation/drivers/{driver}/{locale?}', 'Sluy\LaravelTranslation\Http\Controllers\DriverController@destroy')->name('laravel-translation.driver.destroy');
});
