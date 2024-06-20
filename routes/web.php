<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});



// Clear application cache:
Route::get('/prodClear', function () {
    Artisan::call('cache:clear');
    Artisan::call('route:cache');
    Artisan::call('config:cache');
    Artisan::call('view:clear');
    return 'Success!';
});


Route::get('/migrate', function() {
    Artisan::call('migrate');
    return 'Migrating database.';
});