<?php

use App\Exceptions\ResourceNotFoundException;
use App\Utils\ExceptionMessage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return response()->file(public_path('index.html'));
});

Route::get('/storage/{path}', function ($path) {
    $filePath = Storage::get($path);

    if ($filePath) {
        return response()->file($filePath);
    }

    return response()->json([
        'message' => 'File not found.'
    ], 404);

    throw new ResourceNotFoundException('file', new ExceptionMessage([
        'server' => 'File not found.',
        'client' => 'Arquivo não encontrado.'
    ]));
});
