<?php

declare(strict_types=1);

namespace Modules\User\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Setting\Facades\Settings;

final class AccountBanned extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly ?string $reason = null) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $app = (string) Settings::get('app.name');

        $message = (new MailMessage)
            ->subject(__('user::notification.banned_subject', ['app' => $app]))
            ->line(__('user::notification.banned_line', ['app' => $app]));

        if ($this->reason !== null && $this->reason !== '') {
            $message->line(__('user::notification.banned_reason', ['reason' => $this->reason]));
        }

        return $message;
    }
}
