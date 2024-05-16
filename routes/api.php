<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BlogController;
use Illuminate\Support\Facades\Route;

/////////////////////////////// ROUTES THAT NEED NO AUTHENTICATION //////////////////////////////////////////////////////
//products
Route::get('/products',[ProductController::class,'index'])->name('products.index');
Route::get('/products/{product}',[ProductController::class,'show'])->name('products.show');

//blogs
Route::get('/blogs',[BlogController::class,'index'])->name('blogs.index');
Route::get('/blogs/{blog}',[BlogController::class,'show'])->name('blogs.show');

////////////////////////////// AUTHENTICATION ROUTES ///////////////////////////////////////////////////////////////////
Route::group(['prefix'=>'auth'], function(){
    Route::post('/user/register',[AuthController::class,'register'])->name('user.register');
    Route::post('/user/login',[AuthController::class,'login'])->name('user.login');
    Route::post('/admin/login',[AuthController::class,'loginAdmin'])->name('admin.login');
    Route::get('/refresh-token',[AuthController::class,'refreshToken'])->name('refresh.token');
});

/////////////////////////////// ROUTES THAT REQUIRE AUTHENTICATION AND ACTIVATION //////////////////////////////////////
Route::middleware(['auth:sanctum', 'active'])->group(function(){

    //users
    Route::post('/users',[UserController::class,'store'])->name('users.store');
    Route::put('/users/{user}',[UserController::class,'update'])->name('users.update');
    Route::put('/users/change-password/{user}',[AuthController::class,'updatePassword'])->name('users.changePassword');
    Route::post('/users/forgot-password/{user}',[AuthController::class,'forgotPassword'])->name('users.forgotPassword');
    Route::post('/users/reset-password',[AuthController::class,'resetPassword'])->name('users.resetPassword');
    Route::get('/logout',[AuthController::class,'logout'])->name('user.logout');
});

/////////////////////////////// ROUTES THAT REQUIRE ACTIVATION, AUTHENTICATION AND ADMIN PERMISSIONS ///////////////////
Route::middleware(['auth:sanctum', 'admin', 'active'])->group(function(){

    //products
    Route::post('/products',[ProductController::class,'store'])->name('products.store');
    Route::put('/products/{product}',[ProductController::class,'update'])->name('products.update');
    Route::delete('/products/{product}',[ProductController::class,'destroy'])->name('products.destroy');

    //blogs
    Route::post('/blogs',[BlogController::class,'store'])->name('blogs.store');
    Route::put('/blogs/{blog}',[BlogController::class,'update'])->name('blogs.update');
    Route::delete('/blogs/{blog}',[BlogController::class,'destroy'])->name('blogs.destroy');

    // users
    Route::get('/users',[UserController::class,'index'])->name('users.index');
    Route::get('/users/{user}',[UserController::class,'show'])->name('users.show');
    Route::delete('/users/{user}',[UserController::class,'destroy'])->name('users.destroy');
    Route::get('/block-unblock/{user}',[UserController::class,'blockUnblock'])->name('user.blockUnblock');
    Route::get('/change-role/{user}',[UserController::class,'role'])->name('user.role');
});






