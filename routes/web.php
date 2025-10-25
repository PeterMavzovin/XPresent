<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\BookingController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');




Route::get('/services', [ServiceController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('services.index');

Route::get('/services/{service}', [ServiceController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('services.show');

    
Route::get('/services/{service}/available-slots', [BookingController::class, 'availableSlots'])
    ->name('services.available-slots');


Route::post('/services/{service}/bookings', [BookingController::class, 'store'])
    ->middleware(['auth'])
    ->name('services.book');

    
require __DIR__.'/settings.php';
