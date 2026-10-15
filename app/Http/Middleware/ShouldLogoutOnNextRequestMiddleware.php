<?php


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ShouldLogoutOnNextRequestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next): mixed
    {
        $user = $request->user();

        // We check whether the user should be logged out in order to reload their permissions.
        if ($user->should_logout_on_next_request) {
            throw new HttpResponseException(response()->json([
                'message' => 'SHOULD_LOGOUT_ON_NEXT',
                'type' => 'SHOULD_LOGOUT_ON_NEXT'
            ], Response::HTTP_UNAUTHORIZED));
        }

        return $next($request);
    }
}
