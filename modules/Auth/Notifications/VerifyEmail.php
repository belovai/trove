<?php

declare(strict_types=1);

namespace Modules\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Modules\Setting\Facades\Settings;

final class VerifyEmail extends Notification implements ShouldQueue
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
            ->subject(__('auth::verification.subject', ['app' => $app]))
            ->line(__('auth::verification.line', ['app' => $app]))
            ->action(__('auth::verification.action'), $this->verificationUrl($notifiable))
            ->line(__('auth::verification.ignore'));
    }

    private function verificationUrl(object $notifiable): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $notifiable->getKey(),
            'hash' => sha1((string) $notifiable->getEmailForVerification()),
        ]);
    }
}
