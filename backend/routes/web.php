<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

Route::get('/', function () {
    return view('welcome');
});

// Include broadcast auth routes
Broadcast::routes(['middleware' => ['auth:sanctum']]);
