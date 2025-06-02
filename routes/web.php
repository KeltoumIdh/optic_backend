<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

// List all registered routes (useful for debugging)
Route::get('/routes', function () {
    $routes = collect(Route::getRoutes())->map(function ($route) {
        return [
            'method' => implode('|', $route->methods()),
            'uri' => $route->uri(),
            'name' => $route->getName(),
            'action' => $route->getActionName(),
        ];
    })->sortBy('uri')->values();

    return response()->json($routes);
});


// Clear application cache:
Route::get('/prodClear', function () {
    Artisan::call('cache:clear');
    Artisan::call('route:cache');
    Artisan::call('config:cache');
    Artisan::call('view:clear');
    return 'Success!';
});

// Clear route cache only (useful for debugging route issues)
Route::get('/clear-routes', function () {
    Artisan::call('route:clear');
    return 'Route cache cleared successfully!';
});


Route::get('/migrate', function () {
    Artisan::call('migrate');
    return 'Migrating database.';
});
