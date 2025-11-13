<?php

use App\Http\Controllers\BaseController;
use App\Http\Controllers\CleanerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('api/{version}/clean/name', [CleanerController::class, 'names']);

Route::post('/api/{version}/clean/{path?}', [BaseController::class, 'cleaner'])
    ->where('path', '.*');

Route::post('/suggestions/api/{version}/{path?}', [BaseController::class, 'suggestions'])
    ->where('path', '.*');
