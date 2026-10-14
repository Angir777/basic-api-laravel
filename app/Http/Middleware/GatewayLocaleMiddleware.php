<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GatewayLocaleMiddleware
{
    /**
     * Middleware odpowiada za przechwycenie wybranego jezyka z gateway.
     * Główne zastosowanie to walidacja.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('Locale');
        if ($locale != null) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
