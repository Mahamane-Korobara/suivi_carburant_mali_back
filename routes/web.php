<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/deployment-check', function () {
    return view('deployment-check', [
        'appName' => config('app.name', 'Laravel'),
        'environment' => app()->environment(),
        'phpVersion' => PHP_VERSION,
        'laravelVersion' => app()->version(),
        'timestamp' => now()->toDateTimeString(),
    ]);
})->name('deployment.check');
