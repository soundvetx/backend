<?php

return [
    'ER000' => [
        'error_code' => 'ER000',
        'title' => 'Internal Server Error',
        'message' => [
            'server' => 'An internal server error occurred.',
            'client' => 'Ocorreu um erro interno no servidor.',
        ],
        'http_status_code' => 500
    ],
    'ER001' => [
        'error_code' => 'ER001',
        'title' => 'Bad Request',
        'message' => [
            'server' => 'Some request parameter is invalid.',
            'client' => 'Algum parâmetro da requisição é inválido.',
        ],
        'http_status_code' => 400
    ],
    'ER002' => [
        'error_code' => 'ER002',
        'title' => 'Unauthorized',
        'message' => [
            'server' => 'You do not have permission to access this resource.',
            'client' => 'Você não tem permissão para acessar este recurso.',
        ],
        'http_status_code' => 401
    ],
    'ER003' => [
        'error_code' => 'ER003',
        'title' => 'Resource Not Found',
        'message' => [
            'server' => 'The resource was not found.',
            'client' => 'O recurso não foi encontrado.',
        ],
        'http_status_code' => 404
    ],
    'ER004' => [
        'error_code' => 'ER004',
        'title' => 'Route not found',
        'message' => [
            'server' => 'The requested route was not found.',
            'client' => 'A rota solicitada não foi encontrada.',
        ],
        'http_status_code' => 404
    ],
];
