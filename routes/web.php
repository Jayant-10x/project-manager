<?php

// routes/web.php
use App\Http\Controllers\Api\LegacyGatewayController;
use App\Http\Controllers\Web\AppViewController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AppViewController::class, 'index'])->name('home');

// Handle legacy root-level API dispatch without CSRF verification
Route::match(['GET', 'POST'], '/api.php', [LegacyGatewayController::class, 'dispatch'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);
