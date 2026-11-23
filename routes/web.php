<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DatabaseHealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MercadoPagoWebhookController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\WishlistController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::get('/', HomeController::class)->name('home');
Route::get('/{categoria}', CatalogController::class)->whereIn('categoria', ['ropa', 'zapatos', 'accesorios'])->name('catalog');
Route::get('/producto/{slug}', ProductController::class)->name('products.show');
Route::get('/buscar', SearchController::class)->name('search');

Route::get('/health/db', DatabaseHealthController::class)
    ->middleware('throttle:10,1')
    ->name('health.db');

Route::prefix('carrito')->name('cart.')->controller(CartController::class)->group(function (): void {
    Route::get('/', 'show')->name('show');
    Route::post('/items', 'store')->name('items.store');
    Route::patch('/items/{variant}', 'update')->whereNumber('variant')->name('items.update');
    Route::delete('/items/{variant}', 'destroy')->whereNumber('variant')->name('items.destroy');
});

Route::middleware('auth')->prefix('favoritos')->name('wishlist.')->controller(WishlistController::class)->group(function (): void {
    Route::get('/', 'index')->name('index');
    Route::put('/{product}', 'store')->whereNumber('product')->name('store');
    Route::delete('/{product}', 'destroy')->whereNumber('product')->name('destroy');
});

Route::get('/quienes-somos', [PageController::class, 'about'])->name('about');
Route::get('/politica-de-devolucion-cambios-y-garantias', [PageController::class, 'returns'])->name('returns');
Route::view('/cookies', 'pages.cookies')->name('cookies');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', [PageController::class, 'robots'])->name('robots');

Route::get('/politica-de-privacidad', [PageController::class, 'privacy'])->name('privacy');

Route::middleware('auth')->prefix('cuenta')->name('account.')->controller(AccountController::class)->group(function (): void {
    Route::get('/', 'show')->name('show');
    Route::get('/configuracion', 'edit')->name('edit');
    Route::patch('/configuracion', 'update')->name('update');
    Route::get('/avatar', 'avatar')->name('avatar');
});

Route::post('/webhooks/mercadopago', MercadoPagoWebhookController::class)
    ->withoutMiddleware([
        PreventRequestForgery::class,
        StartSession::class,
        AuthenticateSession::class,
        ShareErrorsFromSession::class,
    ])->name('mercadopago.webhook');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
});
Route::middleware('auth')->prefix('pedidos')->name('orders.')->group(function (): void {
    Route::get('/{order}', [OrderController::class, 'show'])->whereNumber('order')->name('show');
    Route::get('/{order}/resultado', [OrderController::class, 'result'])->whereNumber('order')->name('result');
});

require __DIR__.'/auth.php';
