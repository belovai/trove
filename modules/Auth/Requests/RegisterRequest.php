<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

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
        return match (config('trove.registration.email')) {
            'required' => ['required', 'email', 'max:255', 'unique:users,email'],
            'off' => ['prohibited'],
            default => ['nullable', 'email', 'max:255', 'unique:users,email'],
        };
    }
}
