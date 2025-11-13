<?php

use App\Http\Controllers\BaseController;
use App\Http\Controllers\CleanerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('api/{version}/clean/name', [CleanerController::class, 'name']);
Route::post('api/{version}/clean/phone', [CleanerController::class, 'phone']);
Route::post('api/{version}/clean/email', [CleanerController::class, 'email']);

Route::post('/api/{version}/clean/{path?}', [BaseController::class, 'cleaner'])
    ->where('path', '.*');

Route::post('/suggestions/api/{version}/{path?}', [BaseController::class, 'suggestions'])
    ->where('path', '.*');
