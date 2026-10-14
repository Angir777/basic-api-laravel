<?php

namespace App\Helpers\Access;

use App\Models\User;

abstract class RefreshUserTokenHelper
{
    /**
     * Funkcja wylogowywuje użytkownika by ten widział zmiany wprowadzone na jego rolach
     */
    public static function shouldLogoutOnNextRequest(string $userId): void
    {
        $user = User::where('id', '=', $userId)->first();
        if ($user) {
            $user->update([
                'should_logout_on_next_request' => true,
            ]);
        }
    }
}
