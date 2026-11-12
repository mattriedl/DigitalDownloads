<?php

use App\Http\Controllers\AdController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BrowseController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\FallbackController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ReportController;
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

// Unknown URLs: admin-defined redirects first, then a 404 page
Route::fallback(FallbackController::class);
