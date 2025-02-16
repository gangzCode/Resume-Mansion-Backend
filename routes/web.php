<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'payment'], function ($router) {
    $router->get('/create', [PaymentController::class, 'create'])->name('payment.create');
    $router->post('/create', [PaymentController::class, 'store'])->name('stripe.post');
});

require __DIR__.'/auth.php';