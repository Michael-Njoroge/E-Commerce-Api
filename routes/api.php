<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\BlogController;
use Illuminate\Support\Facades\Route;

//Auth routes
Route::post('/user/register',[AuthController::class,'register'])->name('user.register');
Route::post('/user/login',[AuthController::class,'login'])->name('user.login');
Route::post('/admin/login',[AuthController::class,'loginAdmin'])->name('admin.login');
Route::get('/refresh-token',[AuthController::class,'refreshToken'])->name('refresh.token');

// Routes requiring authentication & activation 
Route::middleware(['auth:sanctum', 'active'])->group(function(){
    //products
    Route::get('/products',[ProductController::class,'index'])->name('products.index');
    Route::get('/products/{product}',[ProductController::class,'show'])->name('products.show');

    Route::get('/logout',[AuthController::class,'logout'])->name('user.logout');
});

// Routes requiring both activation, authentication and admin middleware
Route::middleware(['auth:sanctum', 'admin', 'active'])->group(function(){
    //products
    Route::post('/products',[ProductController::class,'store'])->name('products.store');
    Route::put('/products/{product}',[ProductController::class,'update'])->name('products.update');
    Route::delete('/products/{product}',[ProductController::class,'destroy'])->name('products.destroy');
    // users
    Route::get('/users',[ProductController::class,'index'])->name('users.index');
    Route::post('/users',[ProductController::class,'store'])->name('users.store');
    Route::get('/users/{user}',[ProductController::class,'show'])->name('users.show');
    Route::put('/users/{user}',[ProductController::class,'update'])->name('users.update');
    Route::delete('/users/{user}',[ProductController::class,'destroy'])->name('users.destroy');

    Route::get('/block-unblock/{user}',[UserController::class,'blockUnblock'])->name('user.blockUnblock');
    Route::get('/change-role/{user}',[UserController::class,'role'])->name('user.role');
});






