<?php

declare(strict_types=1);

namespace Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Media\Enums\SafetyRating;

final class UpdateAccountRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'display_name' => ['nullable', 'string', 'max:64'],
            'locale' => ['nullable', 'string', Rule::in(config('trove.locales'))],
            'default_safety_filter' => ['nullable', Rule::enum(SafetyRating::class)],
        ];
    }
}
