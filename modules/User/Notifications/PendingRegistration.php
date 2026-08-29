<?php

declare(strict_types=1);

namespace Modules\User\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Setting\Facades\Settings;

final class PendingRegistration extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly string $username) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $app = (string) Settings::get('app.name');

        return (new MailMessage)
            ->subject(__('user::notification.pending_subject', ['app' => $app]))
            ->line(__('user::notification.pending_line', ['username' => $this->username]))
            ->action(__('user::notification.pending_action'), url('/settings/users'));
    }
}
