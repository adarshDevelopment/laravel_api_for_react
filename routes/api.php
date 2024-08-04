<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PictureController;
use App\Http\Controllers\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/test', function () {
    return 'hello world';
});

Route::apiResource('posts', PostController::class);


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);



// #########   PROTECTED ROUTES 
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');


Route::post('/pictures', [PictureController::class, 'store'])->middleware('auth:sanctum');
Route::get('/pictures', [PictureController::class, 'index']);

Route::delete('/pictures/{id}', [PictureController::class, 'destroy'])->middleware('auth:sanctum');

Route::post('/demo', [PictureController::class, 'demo'])->middleware('auth:sanctum');
