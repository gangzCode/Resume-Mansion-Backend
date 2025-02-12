<?php

use App\Http\Controllers\API\FAQController;
use App\Http\Controllers\API\SliderController;
use App\Http\Controllers\API\LoginController;
use App\Http\Controllers\API\ContactController;
use App\Http\Controllers\API\ReviewController;
use App\Http\Controllers\API\PackageController;
use App\Http\Controllers\API\UploadController;
use App\Http\Controllers\API\CardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::controller(FAQController::class)->group(function () {
    Route::get('faqs', 'faqs');
});

Route::controller(SliderController::class)->group(function () {
    Route::get('sliders', 'index');
});

Route::controller(ContactController::class)->group(function () {
    Route::post('contact-us', 'post');
});
Route::controller(PackageController::class)->group(function () {
    Route::get('packeges', 'index');
    Route::get('packege/view/{id}', 'view');
});

Route::controller(UploadController::class)->group(function () {
    Route::post('cv/upload', 'upload');
});
Route::controller(ReviewController::class)->group(function () {
    Route::get('accept-review', 'acceptReview');
    Route::get('pending-review', 'pendingReview');
});

Route::controller(LoginController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
});


Route::controller(CardController::class)->group(function () {
    Route::get('coupon', 'getCoupon');
});

Route::middleware(['auth:api'])->group(function(){
    Route::controller(CardController::class)->group(function () {
        Route::post('add-to-cart', 'addCart');
        Route::get('get-cart', 'getCart');
        Route::put('cart/update', 'update');
        Route::delete('cart/delete/{id}', 'delete');
        Route::delete('cart/clear/{id}', 'clear');
        Route::put('placeorder', 'placeOrder');
        Route::post('cart/place-order', 'post');
    });
});