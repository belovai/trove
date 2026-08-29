<?php

declare(strict_types=1);

namespace Modules\Mail\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Setting\Facades\Settings;

/**
 * Deliberately not ShouldQueue: the administrator pressing "send test" needs
 * the transport's own answer, not a job id.
 */
final class TestMail extends Notification
{
    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $app = (string) Settings::get('app.name');

        return (new MailMessage)
            ->subject(__('mail::mail.test_subject', ['app' => $app]))
            ->line(__('mail::mail.test_body', ['app' => $app]));
    }
}
