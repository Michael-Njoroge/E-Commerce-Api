<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\BlogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//Auth routes
Route::post('/user',[AuthController::class,'login'])->name('user.login');
Route::post('/admin',[AuthController::class,'loginAdmin'])->name('admin.login');

//Protected routes
Route::middleware('auth:sanctum')->group(function(){
    Route::resource('users',UserController::class);
    Route::resource('products',ProductController::class);
    Route::resource('orders',OrderController::class);
    Route::resource('blogs',BlogController::class);
});



