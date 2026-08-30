<?php

declare(strict_types=1);

namespace Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\User\Enums\AvatarSource;

final class UpdateAvatarRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'source' => [
                'required',
                Rule::enum(AvatarSource::class),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === AvatarSource::Gravatar->value && $this->user()?->email === null) {
                        $fail(__('user::account.avatar_gravatar_requires_email'));
                    }
                },
            ],
            'avatar' => [
                'required_if:source,upload',
                'nullable',
                'image',
                'mimes:jpeg,png,webp,gif',
                'max:'.config('trove.avatar.max_filesize'),
            ],
        ];
    }
}
