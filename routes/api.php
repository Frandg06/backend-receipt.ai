<?php

use App\Http\Controllers\AiController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::post('/ai/process-image', AiController::class);

Route::get('/subir-archivo', function () {
    // 1. Subir un archivo de texto simple
    Storage::put('hola2.txt', 'Contenido de prueba desde Laravel 2');

    // 2. Obtener la URL pública
    $url = Storage::temporaryUrl('hola2.txt', now()->plus(minutes: 5));

    return "Archivo subido. <br> Míralo aquí: <a href='$url' target='_blank'>$url</a>";
});
