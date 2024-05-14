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

// Routes requiring only authentication
Route::middleware('auth:sanctum','active')->group(function(){
    Route::get('/logout',[AuthController::class,'logout'])->name('user.logout');
    Route::resource('products',ProductController::class);
    Route::resource('orders',OrderController::class);
    Route::resource('blogs',BlogController::class);
});

// Routes requiring both authentication and admin middleware
Route::middleware(['auth:sanctum', 'admin', 'active'])->group(function(){
    Route::get('/change-role/{user}',[UserController::class,'role'])->name('user.role');
    Route::resource('users',UserController::class);
    Route::get('/block-unblock/{user}',[UserController::class,'blockUnblock'])->name('user.blockUnblock');
});






