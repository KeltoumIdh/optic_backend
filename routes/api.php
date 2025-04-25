<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\userController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ActivitiesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Catch-all fallback for financial stats (should handle any variant of the request)
Route::any('financial-stats/{any?}', [OrderController::class, 'getFinancialStats'])
    ->where('any', '.*');

// Individual routes for specific timeframes
Route::get('/financial-stats', [OrderController::class, 'getFinancialStats']);
Route::get('/financial-stats/today', [OrderController::class, 'getFinancialStats']);
Route::get('/financial-stats/yesterday', [OrderController::class, 'getFinancialStats']);
Route::get('/financial-stats/this_week', [OrderController::class, 'getFinancialStats']);
Route::get('/financial-stats/last_week', [OrderController::class, 'getFinancialStats']);
Route::get('/financial-stats/this_month', [OrderController::class, 'getFinancialStats']);
Route::get('/financial-stats/last_month', [OrderController::class, 'getFinancialStats']);
Route::get('/financial-stats/this_year', [OrderController::class, 'getFinancialStats']);
Route::get('/financial-stats/last_year', [OrderController::class, 'getFinancialStats']);
Route::get('/financial-stats/all', [OrderController::class, 'getFinancialStats']);
Route::get('/financial-stats/all_time', [OrderController::class, 'getFinancialStats']);

// Statistics route
Route::get('/statistics/{timeframe?}', [OrderController::class, 'getStatistics']);

//CLIENTS
Route::controller(ClientController::class)->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/clients', 'index');
        Route::post('/clients/add', 'create');
        Route::post('/clients/edit/{id}', 'edit');
        Route::post('/clients/update/{id}', 'update');
        Route::delete('/clients/delete/{id}', 'delete');
        Route::get('/clients/details/{id}', 'show');
    });
});

//PRODUCTS
Route::controller(ProductController::class)->middleware(['auth:sanctum'])->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/products', 'index');
        Route::get('/products/get/{id}', 'edit');
        Route::post('/products/add', 'create');
        Route::post('/products/edit/{id}', 'edit');
        Route::get('/products/details/{id}', 'show');
        Route::post('/products/update/{id}', 'update');
        Route::delete('/products/delete/{id}', 'delete');
        Route::get('/products/{id}', 'show');
    });
});

//Orders
Route::controller(OrderController::class)->middleware(['auth:sanctum'])->group(function () {
    Route::get('/orders', 'index');
    Route::get('/orders/add', 'create');
    Route::get('/orders/products/add/{id}', 'createOrder');
    Route::post('/orders/confirmed', 'createOrder');
    Route::get('/orders/confirmed', 'getProductsByIds');
    Route::post('/add-order', 'store');
    Route::post('/orders/edit/{id}', 'edit');
    Route::put('/orders/update/{id}', 'update');
    Route::delete('/orders/delete/{id}', 'delete');
    Route::get('/orders/details/{id}', 'show');
    Route::post('/download-invoice/{orderId}', 'generateInvoice')->name('download.invoice');
    Route::get('/view-invoice/{orderId}', 'viewInvoice')->name('view.invoice');
    Route::put('/confirmOrder', 'confirmOrder');
});

//carts
Route::controller(CartController::class)->middleware(['auth:sanctum'])->group(function () {
    Route::post('/carts/add', 'create');
});

//Users
Route::controller(userController::class)->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/users', 'index');
        Route::post('/users/add', 'create');
        Route::get('/users/edit/{id}', 'edit');
        Route::put('/users/update/{id}', 'update');
        Route::delete('/users/delete/{id}', 'delete');
        Route::post('/users/update/password', 'updatePassword');
    });
});

//Users
Route::controller(ActivitiesController::class)->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/getAllActivities', 'getAllActivities');
    });
});


//Dashboard
Route::controller(DashboardController::class)->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/total/product', 'totalProducts');
        Route::get('/total/client', 'totalClients');
        Route::get('/total/user', 'totalUsers');
        Route::get('/total/order', 'totalOrders');
        Route::get('/top/product', 'getTopSellingProducts');
        Route::get('/stock/product', 'getAvailableProducts');
        Route::get('/credit/clients', 'getClientCredit');
        Route::get('/order/check', 'getPaymentOrders');
        Route::get('/order/facture', 'getFactureOrders');
    });
});




require __DIR__ . '/auth.php';
