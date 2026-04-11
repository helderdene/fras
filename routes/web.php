<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\CameraController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\EventHistoryController;
use App\Http\Controllers\PersonnelController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('api/queue-depth', [DashboardController::class, 'queueDepth'])->name('queue-depth');
    Route::resource('cameras', CameraController::class);
    Route::resource('personnel', PersonnelController::class);
    Route::post('personnel/{personnel}/enrollment/{camera}/retry', [EnrollmentController::class, 'retry'])->name('enrollment.retry');
    Route::post('personnel/{personnel}/enrollment/resync', [EnrollmentController::class, 'resyncAll'])->name('enrollment.resync-all');
    Route::get('alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::post('alerts/{event}/acknowledge', [AlertController::class, 'acknowledge'])->name('alerts.acknowledge');
    Route::post('alerts/{event}/dismiss', [AlertController::class, 'dismiss'])->name('alerts.dismiss');
    Route::get('alerts/{event}/face', [AlertController::class, 'faceImage'])->name('alerts.face-image');
    Route::get('alerts/{event}/scene', [AlertController::class, 'sceneImage'])->name('alerts.scene-image');
    Route::get('events', [EventHistoryController::class, 'index'])->name('events.index');
});

require __DIR__.'/settings.php';
