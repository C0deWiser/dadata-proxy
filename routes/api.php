<?php

use App\Http\Controllers\CleanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('api/{version}/clean/name', [CleanController::class, 'name']);
Route::post('api/{version}/clean/email', [CleanController::class, 'email']);
Route::post('api/{version}/clean/phone', [CleanController::class, 'phone']);
Route::post('api/{version}/clean/address', [CleanController::class, 'address']);
Route::post('api/{version}/clean/vehicle', [CleanController::class, 'vehicle']);
Route::post('api/{version}/clean/passport', [CleanController::class, 'passport']);

Route::post('/api/{version}/clean/{path?}', [CleanController::class, 'fallback'])
    ->where('path', '.*');