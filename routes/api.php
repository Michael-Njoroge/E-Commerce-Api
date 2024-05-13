<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\BlogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//Auth routes
Route::post('/user/register',[AuthController::class,'register'])->name('user.register');
Route::post('/user/login',[AuthController::class,'login'])->name('user.login');
Route::post('/admin/login',[AuthController::class,'loginAdmin'])->name('admin.login');
Route::get('/refresh-token',[AuthController::class,'refreshToken'])->name('refresh.token');

//Protected routes
Route::middleware('auth:sanctum')->group(function(){
    Route::get('/logout',[AuthController::class,'logout'])->name('logout');
    Route::resource('users',UserController::class);
    Route::resource('products',ProductController::class);
    Route::resource('orders',OrderController::class);
    Route::resource('blogs',BlogController::class);
});



