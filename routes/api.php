<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\BlogCategoryController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

/////////////////////////////// ROUTES THAT NEED NO AUTHENTICATION //////////////////////////////////////////////////////
//products
Route::get('/products',[ProductController::class,'index'])->name('products.index');
Route::get('/products/{product}',[ProductController::class,'show'])->name('products.show');

//product categories
Route::get('/product-category',[ProductCategoryController::class,'index'])->name('product-category.index');
Route::get('/product-category/{productCategory}',[ProductCategoryController::class,'show'])->name('product-category.show');

//blog categories
Route::get('/blog-category',[BlogCategoryController::class,'index'])->name('blog-category.index');
Route::get('/blog-category/{blogCategory}',[BlogCategoryController::class,'show'])->name('blog-category.show');

//brand
Route::get('/brands',[BrandController::class,'index'])->name('brand.index');
Route::get('/brands/{brand}',[BrandController::class,'show'])->name('brand.show');

//blogs
Route::get('/blogs',[BlogController::class,'index'])->name('blogs.index');
Route::get('/blogs/{blog}',[BlogController::class,'show'])->name('blogs.show');

//coupons
Route::get('/coupons',[CouponController::class,'index'])->name('coupons.index');
Route::get('/coupons/{coupon}',[CouponController::class,'show'])->name('coupons.show');

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

    //blogs
    Route::put('/blogs/likes/{blog}',[BlogController::class,'likeBlog'])->name('blogs.like');
    Route::put('/blogs/dislikes/{blog}',[BlogController::class,'dislikeBlog'])->name('blogs.dislike');

    //products
    Route::put('/products/{product}/wishlist',[ProductController::class,'addToWishlist'])->name('products.wishlist');
    Route::put('/products/{product}/rate',[ProductController::class,'rateProduct'])->name('products.rate');

    Route::post('/products-blogs/upload',[MediaController::class,'upload'])->name('products.upload');

});

/////////////////////////////// ROUTES THAT REQUIRE ACTIVATION, AUTHENTICATION AND ADMIN PERMISSIONS ///////////////////
Route::middleware(['auth:sanctum', 'admin', 'active'])->group(function(){

    //products
    Route::post('/products',[ProductController::class,'store'])->name('products.store');
    Route::put('/products/{product}',[ProductController::class,'update'])->name('products.update');
    Route::delete('/products/{product}',[ProductController::class,'destroy'])->name('products.destroy');

    //product categories
    Route::post('/product-category',[ProductCategoryController::class,'store'])->name('product-category.store');
    Route::put('/product-category/{productCategory}',[ProductCategoryController::class,'update'])->name('product-category.update');
    Route::delete('/product-category/{productCategory}',[ProductCategoryController::class,'destroy'])->name('product-category.destroy');

    //blog categories
    Route::post('/blog-category',[BlogCategoryController::class,'store'])->name('blog-category.store');
    Route::put('/blog-category/{blogCategory}',[BlogCategoryController::class,'update'])->name('blog-category.update');
    Route::delete('/blog-category/{blogCategory}',[BlogCategoryController::class,'destroy'])->name('blog-category.destroy');

    //blogs
    Route::post('/blogs',[BlogController::class,'store'])->name('blogs.store');
    Route::put('/blogs/{blog}',[BlogController::class,'update'])->name('blogs.update');
    Route::delete('/blogs/{blog}',[BlogController::class,'destroy'])->name('blogs.destroy');

    //brands
    Route::post('/brands',[BrandController::class,'store'])->name('brands.store');
    Route::put('/brands/{brand}',[BrandController::class,'update'])->name('brands.update');
    Route::delete('/brands/{brand}',[BrandController::class,'destroy'])->name('brands.destroy');

    //coupons
    Route::post('/coupons',[CouponController::class,'store'])->name('coupons.store');
    Route::put('/coupons/{coupon}',[CouponController::class,'update'])->name('coupons.update');
    Route::delete('/coupons/{coupon}',[CouponController::class,'destroy'])->name('coupons.destroy');

    // users
    Route::get('/users',[UserController::class,'index'])->name('users.index');
    Route::get('/users/{user}',[UserController::class,'show'])->name('users.show');
    Route::delete('/users/{user}',[UserController::class,'destroy'])->name('users.destroy');
    Route::get('/block-unblock/{user}',[UserController::class,'blockUnblock'])->name('user.blockUnblock');
    Route::get('/change-role/{user}',[UserController::class,'role'])->name('user.role');
});






