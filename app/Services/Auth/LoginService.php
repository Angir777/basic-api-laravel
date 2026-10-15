<?php

namespace App\Services\Auth;

use App\Exceptions\ApiException;
use App\Http\Resources\Auth\AuthenticatedUserResource;
use App\Utils\Response\ResponseUtils;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LoginService
{
    public function login(array $credentials): JsonResponse
    {
        try {
            if (! Auth::attempt($credentials)) {
                throw new ApiException(
                    message: 'UNAUTHORIZED',
                    status: Response::HTTP_UNAUTHORIZED
                );
            }

            $user = Auth::user();

            // Changing the flag for a user who was automatically logged out and is logging in again.
            if ($user && $user->should_logout_on_next_request == true) {
                $user->update([
                    'should_logout_on_next_request' => false,
                ]);
            }

            $user->token = $user->createToken('TokenAPI')->accessToken;

            return ResponseUtils::response(data: new AuthenticatedUserResource($user));
        } catch (ApiException $exception) {
            // Catching business errors.
            throw $exception;
        } catch (\Throwable $exception) {
            // Other errors.
            Log::error($exception->getMessage(), ['exception' => $exception]);
            throw new ApiException(
                message: 'GENERAL_ERROR',
                status: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
