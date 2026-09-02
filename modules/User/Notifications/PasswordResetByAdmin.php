<?php

declare(strict_types=1);

namespace Modules\User\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Setting\Facades\Settings;

/**
 * Carries the fact, never the password: the new one reaches the user through
 * whatever channel the administrator chose.
 */
final class PasswordResetByAdmin extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $app = (string) Settings::get('app.name');

        return (new MailMessage)
            ->subject(__('user::notification.password_reset_subject', ['app' => $app]))
            ->line(__('user::notification.password_reset_line', ['app' => $app]))
            ->line(__('user::notification.password_reset_hint'));
    }
}
