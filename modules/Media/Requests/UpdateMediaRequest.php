<?php

declare(strict_types=1);

namespace Modules\Media\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Media\Enums\SafetyRating;
use Modules\Media\Enums\Visibility;
use Modules\Tag\Support\ResolvesTagInput;

final class UpdateMediaRequest extends FormRequest
{
    use ResolvesTagInput;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'source' => ['nullable', 'string', 'max:255'],
            'visibility' => ['required', Rule::enum(Visibility::class)],
            'safety_rating' => ['required', Rule::enum(SafetyRating::class)],
            'is_anonymous' => ['boolean'],
            'tags' => ['array'],
            'tags.*' => ['string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->boolean('is_anonymous') && $this->input('visibility') === Visibility::Private->value) {
                $validator->errors()->add('is_anonymous', __('media::validation.anonymous_private'));
            }

            $this->validateTagInput($validator);
        });
    }
}
