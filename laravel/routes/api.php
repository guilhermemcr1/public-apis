<?php

use App\Features\GetIp\Http\Controllers\GetIpController;
use Illuminate\Support\Facades\Route;

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/getip', GetIpController::class)
    ->middleware('throttle:getip');
