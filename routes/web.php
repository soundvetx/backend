<?php

use App\Exceptions\ResourceNotFoundException;
use App\Utils\ExceptionMessage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

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

Route::get('/', function () {
    return response()->file(public_path('index.html'));
});

Route::get('/{any}', function ($any) {
    $filePath = public_path($any);

    if (!file_exists($filePath)) {
        return response()->file(public_path('index.html'));
    }

    $mimeType = mime_content_type($filePath);
    if (pathinfo($filePath, PATHINFO_EXTENSION) === 'js') {
        $mimeType = 'application/javascript';
    }

    return response()->file($filePath, ['Content-Type' => $mimeType]);
})->where('any', '^(?!api).*$');
