<?php

declare(strict_types=1);

namespace Modules\User\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Auth\Rules\NotBlockedName;
use Modules\User\Enums\UserRank;
use Modules\User\Models\User;

final class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('user')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User|null $user */
        $user = $this->route('user');

        return [
            'display_name' => ['sometimes', 'nullable', 'string', 'max:64', new NotBlockedName],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'rank' => ['sometimes', Rule::enum(UserRank::class)],
            'is_banned' => ['sometimes', 'boolean'],
            'ban_reason' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $rank = $this->has('rank') ? UserRank::tryFrom((string) $this->input('rank')) : null;

            if ($rank !== null && !$this->user()?->can('assignRank', [User::class, $rank])) {
                $validator->errors()->add('rank', __('user::account.rank_too_high'));
            }
        });
    }
}
