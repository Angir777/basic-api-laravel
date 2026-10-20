<?php

namespace App\Http\Controllers\Api\Auth;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\V1\User\UserResource;
use App\Services\User\UserService;
use App\Utils\Response\ResponseUtils;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Symfony\Component\HttpFoundation\Response;

class RegisterController extends Controller
{
    public function __construct(protected UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Registration uses the website from the user. After successful registration
     * the API returns the response and object of the newly created user.
     *
     * @param  RegisterRequest  $request
     */
    public function register(Request $request): JsonResponse
    {
        $request->merge([
            'locale' => $request->header('Locale', 'pl'),
        ]);

        $validated = $request->validate([
            'firstName' => ['required', 'string', 'max:191'],
            'lastName' => ['required', 'string', 'max:191'],
            'email' => ['required', 'string', 'email', 'max:191', Rule::unique('users')],
            'password' => [
                'required',
                'string',
                'confirmed:passwordConfirmation',
                Rules\Password::defaults(),
                // Password: minimum 8 characters, at least one uppercase and one lowercase letter, at least one digit, no special character required.
            ],
            'acceptanceRegulations' => ['required', 'boolean'],
            'roles' => ['required', 'array', 'size:1'],
            'roles.*' => [Rule::in(['student', 'teacher'])],
            'locale' => ['required', Rule::in(['pl', 'es', 'en'])],
        ]);

        // Creating a new user.
        try {
            // Check if users can register.
            if (! config('access.registration')) {
                throw new ApiException(
                    message: 'REGISTRATION_DISABLED',
                    status: Response::HTTP_BAD_REQUEST
                );
            }

            // Acceptance regulations.
            if (! $request->boolean('acceptanceRegulations')) {
                throw new ApiException(
                    message: 'YOU_DONT_ACCEPTANCE_REGULATIONS',
                    status: Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $data = $this->userService->create(data: $validated);

            return ResponseUtils::response(data: new UserResource($data));
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
     * @param  mixed  $confirmationCode
     */
    public function confirmAccount(Request $request, $confirmationCode): JsonResponse
    {
        try {
            $this->userService->confirmAccount(confirmationCode: $confirmationCode);

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
