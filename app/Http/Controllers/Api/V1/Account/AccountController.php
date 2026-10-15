<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Events\UserRegistered;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\User\UserResource;
use App\Http\Resources\V1\User\UserSettingsResource;
use App\Models\User;
use App\Services\Account\AccountService;
use App\Utils\Response\ResponseUtils;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;
use Symfony\Component\HttpFoundation\Response;

class AccountController extends Controller
{
    public function __construct(protected AccountService $accountService) {}

    public function changePassword(Request $request): JsonResponse
    {
        $user = User::findOrFail(Auth::id());

        $validated = $request->validate([
            'oldPassword' => ['required'],
            'newPassword' => [
                'required',
                'same:passwordConfirmation',
                Rules\Password::defaults(),
            ],
            'passwordConfirmation' => ['required'],
        ]);

        if (! Hash::check($validated['oldPassword'], $user->password)) {
            throw new ApiException(message: 'OLD_PASSWORD_INCORRECT', status: Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->accountService->changePassword(user: $user, data: $validated);

            // Log out of all devices.
            $this->revokeAllDevices($user);

            return ResponseUtils::response(data: new UserResource($user));
        } catch (\Throwable $exception) {
            throw $exception;
        } catch (Exception $exception) {
            Log::error($exception->getMessage(), ['exception' => $exception]);
            throw new ApiException(message: 'CANT_CHANGE_PASSWORD', status: Response::HTTP_BAD_REQUEST);
        }
    }

    public function resendConfirmationEmail(Request $request): JsonResponse
    {
        $user = User::findOrFail(Auth::id());

        if ($user->confirmed) {
            throw new ApiException(
                message: 'EMAIL_ALREADY_CONFIRMED',
                status: Response::HTTP_BAD_REQUEST,
            );
        }

        $cacheKey = 'confirmation-email-sent:'.$user->id;
        if (Cache::has($cacheKey)) {
            throw new ApiException(
                message: 'EMAIL_HAS_BEEN_SENT',
                status: Response::HTTP_TOO_MANY_REQUESTS,
            );
        }

        try {
            $user->update([
                'confirmation_code' => md5(uniqid(mt_rand(), true)),
            ]);

            event(new UserRegistered(
                user: $user,
                notificationLocale: $request->input('locale', config('app.locale')),
            ));

            Cache::put($cacheKey, true, now()->addHour());

            return ResponseUtils::response(data: []);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage(), ['exception' => $exception]);
            throw new ApiException(message: 'CANT_RESEND_CONFIRMATION_EMAIL', status: Response::HTTP_BAD_REQUEST);
        }
    }

    public function updateDetailsUser(Request $request): JsonResponse
    {
        $user = User::findOrFail(Auth::id());

        $validated = $request->validate([
            'firstName' => ['required', 'string', 'max:191'],
            'lastName' => ['required', 'string', 'max:191'],
            'avatar' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp,gif',
                'max:5120',
            ],
            'removeAvatar' => ['nullable', 'in:true,false'],
        ]);

        try {
            $user = $this->accountService->updateDetailsUser(user: $user, data: $validated);

            return ResponseUtils::response(data: new UserResource($user));
        } catch (\Throwable $exception) {
            throw $exception;
        } catch (Exception $exception) {
            Log::error($exception->getMessage(), ['exception' => $exception]);
            throw new ApiException(message: 'CANT_CHANGE_DETAILS', status: Response::HTTP_BAD_REQUEST);
        }
    }

    public function getUserSettings(): JsonResponse
    {
        $user = User::findOrFail(Auth::id());

        return ResponseUtils::response(data: new UserSettingsResource($this->accountService->getUserSettings($user)));
    }

    public function updateUserSettings(Request $request): JsonResponse
    {
        $user = User::findOrFail(Auth::id());

        $validated = $request->validate([
            'webNotifications' => ['sometimes', 'boolean'], // In-app notifications
            'mailNotifications' => ['sometimes', 'boolean'], // Email notifications
        ]);

        try {
            $settings = $this->accountService->updateUserSettings(user: $user, data: $validated);

            return ResponseUtils::response(data: new UserSettingsResource($settings));
        } catch (\Throwable $exception) {
            throw $exception;
        } catch (Exception $exception) {
            Log::error($exception->getMessage(), ['exception' => $exception]);
            throw new ApiException(message: 'CANT_UPDATE_SETTINGS', status: Response::HTTP_BAD_REQUEST);
        }
    }

    private function revokeAllDevices(User $user)
    {
        // Log out of all devices
        $tokens = $user->tokens->pluck('id');
        Token::whereIn('id', $tokens)->update(['revoked' => true]);
        RefreshToken::whereIn('access_token_id', $tokens)->update(['revoked' => true]);
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        $user = User::findOrFail(Auth::id());

        $this->checkPermissionToSuperAdmin(user: $user);

        try {
            // Log out of all devices.
            $this->revokeAllDevices($user);

            // Delete account.
            $user->delete();

            return ResponseUtils::response(data: []);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage(), ['exception' => $exception]);
            throw new ApiException(message: 'CANT_DELETE_ACCOUNT', status: Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Checks whether the user has the 'SUPER_ADMIN' permission.
     * If there is superadmin, it returns an error.
     */
    private function checkPermissionToSuperAdmin(User $user): void
    {
        if ($user->can('BASE_SUPER_ADMIN_ACCESS')) {
            throw new ApiException(
                message: 'SUPER_ADMIN_CANT_DELETE_ACCOUNT',
                status: Response::HTTP_BAD_REQUEST
            );
        }
    }
}
