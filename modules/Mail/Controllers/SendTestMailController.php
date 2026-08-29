<?php

declare(strict_types=1);

namespace Modules\Mail\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Modules\Mail\Notifications\TestMail;
use Modules\Mail\Support\MailConfigurator;
use Throwable;

final class SendTestMailController
{
    public function __invoke(Request $request, MailConfigurator $configurator): RedirectResponse
    {
        abort_unless($request->user()?->can('setting.manage') ?? false, 403);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        // Send through exactly what is stored right now, not through whatever
        // this process configured when it booted.
        $configurator->apply();

        try {
            Notification::route('mail', $validated['email'])->notifyNow(new TestMail);
        } catch (Throwable $exception) {
            // The recipient of this message is an administrator, so the
            // server's own wording is more useful than a sanitized one.
            return back()->with('error', __('mail::mail.test_failed', ['error' => $exception->getMessage()]));
        }

        return back()->with('success', __('mail::mail.test_sent', ['email' => $validated['email']]));
    }
}
