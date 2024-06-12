<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DestinationController;

Route::get('/', function () {
    return view('destinations.index');
});

Route::resource('destinations', DestinationController::class);
