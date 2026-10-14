<?php

namespace App\Notifications;

use App\Enums\EQueues;
use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserPasswordResetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected User $user,
        protected PasswordReset $passwordReset,
        protected $redirectUrl = null,
        protected $resetPasswordLink = ''
    ) {
        $this->onQueue(EQueues::RESET_PASSWORD_EMAILS);

        $this->user = $user;
        $this->passwordReset = $passwordReset;

        $gatewayUrl = config('app.gateway_url');
        if ($redirectUrl !== null) {
            $gatewayUrl = $redirectUrl; // Jeśli przesłany został też URL do APP to używamy go
        }

        // Normalizacja URL'a
        $gatewayUrl = rtrim(config('app.gateway_url'), '/');

        if (config('app.gateway_use_hash')) {
            $this->resetPasswordLink = $gatewayUrl.'/#/password-reset-finish/'.$this->passwordReset->token;
        } else {
            $this->resetPasswordLink = $gatewayUrl.'/password-reset-finish/'.$this->passwordReset->token;
        }
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $subject = __('notifications.reset_password.subject');
        if (app()->environment('local')) {
            $subject = trim(config('mail.subject_prefix').' '.$subject);
        }

        return (new MailMessage)
            ->greeting(__('notifications.reset_password.greeting'))
            ->subject($subject)
            ->line(__('notifications.reset_password.line_one'))
            ->action(__('notifications.reset_password.action'), $this->resetPasswordLink)
            ->line(__('notifications.reset_password.line_two'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toArray($notifiable): array
    {
        return [
            //
        ];
    }
}
