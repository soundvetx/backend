<?php

namespace App\Http\Middleware;

use App\Utils\Transformer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureParametersCase
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $parameters = Transformer::snakeToCamelCase($request->all());

        $request->replace($parameters);

        return $next($request);
    }
}
