<?php

namespace App\Services\Auth;

use App\Exceptions\ApiException;
use App\Models\PasswordReset;
use App\Models\User;
use App\Notifications\UserPasswordResetNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ResetPasswordService
{
    /**
     * @param  mixed  $redirectUrl
     */
    public function sendResetPasswordEmail(array $data): void
    {
        DB::transaction(function () use ($data) {
            // Redirection address in e-mail.
            $redirectUrl = $data['gatewayUrl'] ?? null;

            $user = User::where('email', $data['email'])->first();

            if (! $user) {
                throw new ApiException(
                    message: 'USER_NOT_FOUND',
                    status: Response::HTTP_NOT_FOUND
                );
            }

            $passwordReset = PasswordReset::updateOrCreate(
                ['email' => $user->email],
                ['token' => Str::random(60)]
            );

            if ($passwordReset) {
                $user->notify((new UserPasswordResetNotification(user: $user, passwordReset: $passwordReset, redirectUrl: $redirectUrl))->locale(app()->getLocale()));
            }
        });
    }

    public function finishPasswordReset(array $data): void
    {
        $passwordReset = PasswordReset::where([
            ['token', '=', $data['token']],
            ['email', '=', $data['email']],
        ])->first();

        if (! $passwordReset) {
            throw new ApiException(
                message: 'TOKEN_INVALID',
                status: Response::HTTP_BAD_REQUEST
            );
        }

        if (Carbon::parse($passwordReset->updated_at)->addMinutes(config('auth.passwords.users.expire'))->isPast()) {
            $passwordReset->delete();

            throw new ApiException(
                message: 'TOKEN_EXPIRED',
                status: Response::HTTP_BAD_REQUEST
            );
        }

        $user = User::where('email', $passwordReset->email)->first();

        if (! $user) {
            throw new ApiException(
                message: 'USER_NOT_FOUND',
                status: Response::HTTP_BAD_REQUEST
            );
        }

        $res = $user->update([
            'password' => Hash::make($data['password']),
        ]);

        if (! $res) {
            throw new Exception('CANT_RESET_PASSWORD');
        }

        $passwordReset->delete();
    }
}
