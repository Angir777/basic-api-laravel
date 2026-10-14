<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Notifications\AccountConfirmationNotification;

class SendAccountConfirmationEmail
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserRegistered $event): void
    {
        $notificationLocale = $event->notificationLocale;
        $user = $event->user;

        if (config('access.confirm_email')) {
            if ($user->confirmed === false) {
                $user->notify(new AccountConfirmationNotification(confirmationCode: $user->confirmation_code, notificationLocale: $notificationLocale));
            } else {
                $user->notify(new AccountConfirmationNotification(notificationLocale: $notificationLocale));
            }
        } else {
            $user->notify(new AccountConfirmationNotification(notificationLocale: $notificationLocale));
        }
    }
}
