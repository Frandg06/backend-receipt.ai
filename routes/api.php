<?php

use App\Http\Controllers\AiController;
use Illuminate\Support\Facades\Route;

Route::post('/ai/process-image', AiController::class)->middleware('token');
