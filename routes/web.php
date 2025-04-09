<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\chatController;
use App\Models\Thread;

Route::get('/', function () {
    // return Inertia::render('Welcome', [
    //     'canLogin' => Route::has('login'),
    //     'canRegister' => Route::has('register'),
    //     'laravelVersion' => Application::VERSION,
    //     'phpVersion' => PHP_VERSION,
    // ]);
    return Inertia::location(route('login'));
});

Route::get('/dashboard', function () {
    $threads = Thread::orderBy('created_at', 'desc')->limit(500)->get();
    return Inertia::render('Dashboard', [
        'threads' => $threads
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('api/chat-receive', [chatController::class, 'receive']);
Route::post('api/model-test', [chatController::class, 'modelTest']);

require __DIR__.'/auth.php';
