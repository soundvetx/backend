<?php

use App\Exceptions\BaseException;
use App\Utils\ExceptionMessage;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $exception) {
            if ($exception instanceof NotFoundHttpException) {
                if (!request()->is('api/*')) {
                    return redirect('/');
                }

                throw new BaseException('ER004');
            }

            if (request()->is('api/*')) {
                throw new BaseException('ER000', new ExceptionMessage([
                    'server' => $exception->getMessage(),
                    'client' => 'Ocorreu um erro interno no servidor.',
                ]));
            }
        });
    })->create();
