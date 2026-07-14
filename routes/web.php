<?php

use App\Http\Controllers\Admin\AdminHomeController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DatasetController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\ApiDocsController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SubscriberDashboardController;
use App\Http\Controllers\UserOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/demo', [DemoController::class, 'index'])->name('demo');

Route::get('/test', [HomeController::class, 'test'])->name('test');
Route::get('/privacy-policy', [HomeController::class, 'privacyPolicy'])->name('privacy-policy');
Route::get('/refund-cancellation', [HomeController::class, 'refundCancellation'])->name('refund-cancellation');
Route::get('/terms', [HomeController::class, 'terms'])->name('terms');
Route::get('/fair-usage', [HomeController::class, 'fairUsage'])->name('fair-usage');
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/services', [ShopController::class, 'index'])->name('services.index');
Route::get('/services/{product}', [ShopController::class, 'show'])->name('services.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
Route::patch('/cart/{id}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{id}', [CartController::class, 'destroy'])->name('cart.destroy');
Route::delete('/cart-clear', [CartController::class, 'clear'])->name('cart.clear');

Route::get('/checkout', [CheckoutController::class, 'index'])->middleware(['auth', 'throttle:checkout'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware(['auth', 'throttle:checkout'])->name('checkout.store');

Route::post('/cart/discount', [DiscountController::class, 'apply'])->name('cart.discount.apply');
Route::delete('/cart/discount', [DiscountController::class, 'remove'])->name('cart.discount.remove');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'admin'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/home', [AdminHomeController::class, 'edit'])->name('home.edit');
    Route::post('/home', [AdminHomeController::class, 'update'])->name('home.update');

    Route::resource('products', ProductController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('datasets', DatasetController::class);

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::patch('/orders/{order}/update-status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::post('/customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');
    Route::post('/customers/{customer}/toggle-vip', [CustomerController::class, 'toggleVip'])->name('customers.toggle-vip');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/licenses', [InventoryController::class, 'index'])->name('licenses.index');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/orders', [UserOrderController::class, 'index'])->name('orders.index');
    Route::get('/payment/checkout/{order}', [\App\Http\Controllers\PaymentController::class, 'checkout'])->name('payment.checkout');
    Route::get('/payment/callback', [\App\Http\Controllers\PaymentController::class, 'callback'])->name('payment.callback');
});

Route::middleware(['auth', 'verified', 'subscribed'])->prefix('pro-dashboard')->name('pro-dashboard.')->group(function () {
    Route::get('/', [SubscriberDashboardController::class, 'index'])->name('index');
    Route::get('/products', [SubscriberDashboardController::class, 'products'])->name('products.index');
});

Route::middleware(['auth', 'verified', 'has.dataset.access'])->group(function () {
    Route::get('/downloads/{dataset}', [DownloadController::class, 'download'])->name('downloads.dataset');
});

Route::middleware(['auth', 'verified', 'has.api.access'])->group(function () {
    Route::get('/developer/docs', [ApiDocsController::class, 'index'])->name('developer.docs');
});

require __DIR__.'/auth.php';

Route::middleware('auth')->group(function () {
    Route::post('/favorites/{product}/toggle', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
});
