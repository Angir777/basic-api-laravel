<?php

namespace App\Http\Controllers\Api\Auth;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendResetPasswordEmailRequest;
use App\Services\Auth\ResetPasswordService;
use App\Utils\Response\ResponseUtils;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Symfony\Component\HttpFoundation\Response;

class ResetPasswordController extends Controller
{
    protected $resetPasswordService;

    public function __construct(ResetPasswordService $resetPasswordService)
    {
        $this->resetPasswordService = $resetPasswordService;
    }

    /**
     * Step 1
     * The user sends a request to the API to reset the password.
     * The request is sent from the APP, so we have its address and then return the link in the e-mail.
     * By clicking the link in the email, the user is taken back to the APP with a password reset view.
     *
     * @param  SendResetPasswordEmailRequest  $request
     */
    public function sendResetPasswordEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'gatewayUrl' => ['nullable', 'string'],
        ]);

        try {
            $this->resetPasswordService->sendResetPasswordEmail(data: $validated);

            return ResponseUtils::response(data: []);
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

    /**
     * Step 2
     * The user with an active token (the one sent in the e-mail) can change his current password.
     */
    public function finishPasswordReset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => [
                'required',
                'string',
                'confirmed:passwordConfirmation',
                Rules\Password::defaults(),
            ],
            'token' => ['required', 'string'],
        ]);

        try {
            $this->resetPasswordService->finishPasswordReset(data: $validated);

            return ResponseUtils::response(data: []);
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
