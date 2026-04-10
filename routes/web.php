<?php

use App\Http\Controllers\CameraController;
use App\Http\Controllers\PersonnelController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::resource('cameras', CameraController::class);
    Route::resource('personnel', PersonnelController::class);
});

require __DIR__.'/settings.php';
