<?php

use Illuminate\Support\Facades\Route;
use function OpenApi\scan;

Route::get('/', function () {
    return view('home');
});

Route::get('/docs', function () {
    $path    = app_path();
    $openapi = scan($path);

    return $openapi->toYaml();
});
