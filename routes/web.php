<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BrowseController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\FallbackController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Browsing
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/category/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('/popular', [BrowseController::class, 'popular'])->name('popular');
Route::get('/latest', [BrowseController::class, 'latest'])->name('latest');
Route::get('/top-rated', [BrowseController::class, 'topRated'])->name('top-rated');
Route::get('/tag/{tag:slug}', [BrowseController::class, 'tag'])->name('tags.show');
Route::get('/search', [BrowseController::class, 'search'])->name('search');

// Items and the download flow: item page -> countdown page (ads) -> signed link that counts and redirects
Route::get('/download/{item:slug}', [ItemController::class, 'show'])->name('items.show');
Route::get('/download/{item:slug}/get/{link?}', [DownloadController::class, 'countdown'])->name('items.get');
Route::get('/download/{item:slug}/go/{link}', [DownloadController::class, 'go'])
    ->middleware(['signed', 'throttle:30,1'])->name('items.go');
Route::post('/download/{item:slug}/report', [ReportController::class, 'store'])
    ->middleware('throttle:5,10')->name('items.report');

// Accounts (sign-in, sign-up and password pages come from Fortify)
Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleController::class, 'callback']);

Route::middleware('auth')->group(function () {
    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::get('/account/settings', [AccountController::class, 'settings'])->name('account.settings');
    Route::delete('/account', [AccountController::class, 'destroy'])->name('account.destroy');
    Route::post('/download/{item:slug}/save', [AccountController::class, 'toggleFavorite'])->name('items.favorite');

    Route::get('/account/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/account/tickets/new', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/account/tickets', [TicketController::class, 'store'])->middleware('throttle:5,10')->name('tickets.store');
    Route::get('/account/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/account/tickets/{ticket}/reply', [TicketController::class, 'reply'])->middleware('throttle:20,10')->name('tickets.reply');
    Route::post('/account/tickets/{ticket}/close', [TicketController::class, 'close'])->name('tickets.close');
    Route::get('/account/tickets/{ticket}/files/{attachment}', [TicketController::class, 'attachment'])->name('tickets.attachment');

    Route::middleware(['verified', 'throttle:10,10'])->group(function () {
        Route::post('/download/{item:slug}/review', [FeedbackController::class, 'review'])->name('items.review');
        Route::post('/download/{item:slug}/comment', [FeedbackController::class, 'comment'])->name('items.comment');
    });
});

// Content
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/category/{blogCategory:slug}', [BlogController::class, 'category'])->name('blog.category');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/page/{page:slug}', [PageController::class, 'show'])->name('pages.show');
Route::get('/contact', [ContactController::class, 'create'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,10')->name('contact.store');

// Ads
Route::get('/ads/{ad}/click', [AdController::class, 'click'])->middleware('throttle:60,1')->name('ads.click');
Route::get('/ads.txt', [AdController::class, 'adsTxt'])->name('ads.txt');

// Search engines
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'index'])->name('sitemap.index');
Route::get('/sitemaps/items-{page}.xml', [SeoController::class, 'items'])->whereNumber('page')->name('sitemap.items');
Route::get('/sitemaps/{section}.xml', [SeoController::class, 'section'])->whereIn('section', ['categories', 'blog', 'pages'])->name('sitemap.section');
Route::get('/{key}.txt', [SeoController::class, 'indexNowKey'])->where('key', '[a-f0-9]{32}')->name('indexnow.key');

// Unknown URLs: admin-defined redirects first, then a 404 page
Route::fallback(FallbackController::class);
