<?php

namespace App\Services\Auth;

use App\Utils\Response\ResponseUtils;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;

class LogoutService
{
    /**
     * @param  mixed  $data
     * @return array
     */
    public function logout(array $data): JsonResponse
    {
        $user = Auth::user();

        if (isset($data['revokeAll'])) {
            if ($data['revokeAll']) {
                $this->revokeAllDevices($user);
            } else {
                $this->revokeCurrentDevice($user);
            }
        } else {
            if (! config('auth.on_logout_revoke_all_tokens')) {
                $this->revokeCurrentDevice($user);
            } else {
                $this->revokeAllDevices($user);
            }
        }

        return ResponseUtils::response(data: null, message: 'LOGGED_OUT');
    }

    /**
     * @param  mixed  $user
     */
    private function revokeAllDevices($user)
    {
        // Log out of all devices
        $tokens = $user->tokens->pluck('id');
        Token::whereIn('id', $tokens)->update(['revoked' => true]);
        RefreshToken::whereIn('access_token_id', $tokens)->update(['revoked' => true]);
    }

    /**
     * @param  mixed  $user
     */
    private function revokeCurrentDevice($user)
    {
        // Log out only from the current device (other tokens remain active)
        $userToken = $user->token();
        $userToken->revoke();
    }
}
