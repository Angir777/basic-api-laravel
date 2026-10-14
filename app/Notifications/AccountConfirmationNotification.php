<?php

namespace App\Notifications;

use App\Enums\EQueues;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class AccountConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private ?string $confirmationCode = null,
        private string $notificationLocale = 'en',
    ) {
        $this->onQueue(EQueues::REGISTER_EMAILS);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        App::setLocale($this->notificationLocale);

        $subject = __('notifications.account_confirmation.subject_welcome');
        if (app()->environment('local')) {
            $subject = trim(config('mail.subject_prefix').' '.$subject);
        }

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting(__('notifications.account_confirmation.greeting', ['first_name' => $notifiable->first_name]))
            ->line(__('notifications.account_confirmation.account_created'));

        if ($this->confirmationCode) {
            if (config('app.gateway_use_hash')) {
                $url = config('app.gateway_url').'/#/confirm-account/'.$this->confirmationCode;
            } else {
                $url = config('app.gateway_url').'/confirm-account/'.$this->confirmationCode;
            }

            $mail
                ->line(__('notifications.account_confirmation.confirm_email'))
                ->action(
                    __('notifications.account_confirmation.action_confirm'),
                    url($url)
                )
                ->line(__('notifications.account_confirmation.ignore_if_not_created'));
        } else {
            $mail->line(__('notifications.account_confirmation.can_login'));
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
