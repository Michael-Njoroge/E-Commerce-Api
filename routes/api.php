<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\BlogCategoryController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\EnquiryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\StripeWebhookController;
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

//color
Route::get('/colors',[ColorController::class,'index'])->name('color.index');
Route::get('/colors/{color}',[ColorController::class,'show'])->name('color.show');

//blogs
Route::get('/blogs',[BlogController::class,'index'])->name('blogs.index');
Route::get('/blogs/{blog}',[BlogController::class,'show'])->name('blogs.show');

//coupons
Route::get('/coupons',[CouponController::class,'index'])->name('coupons.index');
Route::get('/coupons/{coupon}',[CouponController::class,'show'])->name('coupons.show'); 
Route::post('/products-blogs/upload',[MediaController::class,'upload'])->name('products.upload');

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('handle.webhook');
Route::post('/users/forgot-password',[AuthController::class,'forgotPassword'])->name('users.forgotPassword');
Route::post('/users/reset-password',[AuthController::class,'resetPassword'])->name('users.resetPassword');

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
    Route::put('/users/update',[UserController::class,'update'])->name('users.update');
    Route::put('/users/change-password/{user}',[AuthController::class,'updatePassword'])->name('users.changePassword');
    Route::put('/users/save-address/{user}',[UserController::class,'saveAddress'])->name('save-address');

    //blogs
    Route::put('/blogs/likes/{blog}',[BlogController::class,'likeBlog'])->name('blogs.like');
    Route::put('/blogs/dislikes/{blog}',[BlogController::class,'dislikeBlog'])->name('blogs.dislike');

    //enquiries
    Route::post('/enquiries',[EnquiryController::class,'store'])->name('enquiries.store');

    //products
    Route::put('/wishlist',[ProductController::class,'addToWishlist'])->name('products.wishlist');
    Route::get('/wishlist',[ProductController::class,'getWishlist'])->name('products.get.wishlist');
    Route::put('/products/rate',[ProductController::class,'rateProduct'])->name('products.rate');

    //cart
    Route::post('/products/add-cart',[UserController::class,'addToCart'])->name('products.cart');
    Route::post('/products/remove-cart',[UserController::class,'removeProductFromCart'])->name('products.remove.cart');
    Route::post('/products/update/cart/quantity',[UserController::class,'updateProductQuantity'])->name('products.update.cart');
    Route::get('/user-cart',[UserController::class,'getUserCart'])->name('user.cart');
    Route::delete('/empty-user-cart',[UserController::class,'emptyUserCart'])->name('empty.user.cart');

    //coupons
    Route::post('/cart/apply-coupon',[UserController::class,'applyCoupon'])->name('apply.coupon');
   
    Route::delete('/products-blogs/delete-img',[MediaController::class,'deleteFromCloudinary'])->name('products.delete');
    Route::post('/products/store/orders',[UserController::class,'createOrder'])->name('products.order');
    Route::get('/user-orders',[UserController::class,'getUserOrders'])->name('get.user.orders');

    //orders
    Route::post('/create-checkout-session', [CheckoutController::class, 'createCheckoutSession'])->name('create.payment');
    Route::get('/orders-month-wise', [UserController::class, 'getOrdersMonthWise'])->name('orders.month');
    Route::get('/orders-yearly-total', [UserController::class, 'getYearlyTotalOrders'])->name('orders.yearly.total');

  
});

/////////////////////////////// ROUTES THAT REQUIRE ACTIVATION, AUTHENTICATION AND ADMIN PERMISSIONS ///////////////////
Route::middleware(['auth:sanctum', 'admin', 'active'])->group(function(){

    //products
    Route::post('/products',[ProductController::class,'store'])->name('products.store');
    Route::put('/products/{product}',[ProductController::class,'update'])->name('products.update');
    Route::delete('/products/{product}',[ProductController::class,'destroy'])->name('products.destroy');

    //orders
    Route::put('/status/order/{order}',[UserController::class,'updateOrderStatus'])->name('products.update.status');
    Route::get('/all-orders',[UserController::class,'getAllOrders'])->name('get.allOrders');


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

    //colors
    Route::post('/colors',[ColorController::class,'store'])->name('colors.store');
    Route::put('/colors/{color}',[ColorController::class,'update'])->name('colors.update');
    Route::delete('/colors/{color}',[ColorController::class,'destroy'])->name('colors.destroy');

    //enquiry
    Route::put('/enquiries/{enquiry}',[EnquiryController::class,'update'])->name('enquiries.update');
    Route::delete('/enquiries/{enquiry}',[EnquiryController::class,'destroy'])->name('enquiries.destroy');
    Route::get('/enquiries',[EnquiryController::class,'index'])->name('enquiries.index');
    Route::get('/enquiries/{enquiry}',[EnquiryController::class,'show'])->name('enquiries.show');


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








