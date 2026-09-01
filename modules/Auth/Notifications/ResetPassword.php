<?php

declare(strict_types=1);

namespace Modules\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Setting\Facades\Settings;
use Modules\User\Models\User;

final class ResetPassword extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(private readonly string $token) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var User $notifiable */
        $app = (string) Settings::get('app.name');

        return (new MailMessage)
            ->subject(__('auth::password.subject', ['app' => $app]))
            ->line(__('auth::password.line', ['app' => $app]))
            ->action(__('auth::password.action'), url("/reset-password/{$this->token}?email=".urlencode((string) $notifiable->getEmailForPasswordReset())))
            ->line(__('auth::password.expires', ['count' => config('auth.passwords.users.expire')]))
            ->line(__('auth::password.ignore'));
    }
}
