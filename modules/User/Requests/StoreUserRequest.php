<?php

declare(strict_types=1);

namespace Modules\User\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\Auth\Rules\NotBlockedName;
use Modules\User\Enums\UserRank;
use Modules\User\Models\User;

final class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:32', 'regex:/^[a-z0-9_]+$/i', Rule::unique('users', 'username'), new NotBlockedName],
            'display_name' => ['nullable', 'string', 'max:64', new NotBlockedName],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::min(8)],
            'rank' => ['required', Rule::enum(UserRank::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $rank = UserRank::tryFrom((string) $this->input('rank'));

            if ($rank !== null && !$this->user()?->can('assignRank', [User::class, $rank])) {
                $validator->errors()->add('rank', __('user::account.rank_too_high'));
            }
        });
    }
}
