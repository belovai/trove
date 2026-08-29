<?php

declare(strict_types=1);

namespace Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Media\Enums\SafetyRating;
use Modules\Media\Enums\Visibility;

final class UpdateAccountRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Every field is `sometimes`: the account and profile sections
            // submit different subsets of the same endpoint.
            'display_name' => ['sometimes', 'nullable', 'string', 'max:64'],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()?->id),
            ],
            'locale' => ['sometimes', 'nullable', 'string', Rule::in(config('trove.locales'))],
            'default_safety_filter' => ['sometimes', 'nullable', Rule::enum(SafetyRating::class)],
            // Null means "use the system default".
            'default_visibility' => ['sometimes', 'nullable', Rule::enum(Visibility::class)],
        ];
    }
}
