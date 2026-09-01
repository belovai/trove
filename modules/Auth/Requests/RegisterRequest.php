<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use LogicException;
use Modules\Auth\Enums\RegistrationEmailPolicy;
use Modules\Auth\Rules\NotBlockedName;
use Modules\Setting\Facades\Settings;

final class RegisterRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'username' => [
                'required',
                'string',
                'min:2',
                'max:32',
                'regex:/^[a-zA-Z0-9_-]+$/',
                'unique:users,username',
                new NotBlockedName,
            ],
            'email' => $this->emailRules(),
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return list<mixed>
     */
    private function emailRules(): array
    {
        return match (Settings::get('registration.email')) {
            RegistrationEmailPolicy::Required => ['required', 'email', 'max:255', 'unique:users,email'],
            RegistrationEmailPolicy::Off => ['prohibited'],
            RegistrationEmailPolicy::Optional => ['nullable', 'email', 'max:255', 'unique:users,email'],
            default => throw new LogicException('Unknown registration email policy.'),
        };
    }
}
