<?php

use App\Http\Controllers\chatController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('api/chat-receive', [chatController::class, 'receive']);

Route::post('api/model-test', [chatController::class, 'modelTest']);
