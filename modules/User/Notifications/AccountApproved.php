<?php

declare(strict_types=1);

namespace Modules\User\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Setting\Facades\Settings;

final class AccountApproved extends Notification implements ShouldQueue
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
            ->subject(__('user::notification.approved_subject', ['app' => $app]))
            ->line(__('user::notification.approved_line', ['app' => $app]))
            ->action(__('user::notification.approved_action'), url('/login'));
    }
}
