<?php

use App\Features\GetIp\Http\Controllers\GetIpController;
use App\Features\GetUuid\Http\Controllers\GetUuidController;
use Illuminate\Support\Facades\Route;

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/getip', GetIpController::class)
    ->middleware('throttle:getip');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/getuuid', GetUuidController::class)
    ->middleware('throttle:getuuid');
