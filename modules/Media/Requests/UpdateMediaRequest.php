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
        // Every field is `sometimes`: the details slide-over and the inline
        // tag editor submit disjoint subsets of the same endpoint.
        return [
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'source' => ['sometimes', 'nullable', 'string', 'max:255'],
            'visibility' => ['sometimes', Rule::enum(Visibility::class)],
            'safety_rating' => ['sometimes', Rule::enum(SafetyRating::class)],
            'is_anonymous' => ['sometimes', 'boolean'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            // Only judge the pair when both halves were submitted; a
            // details-only or tags-only payload may send neither. The
            // controller falls back to the stored values for the rest.
            if ($this->boolean('is_anonymous') && $this->input('visibility') === Visibility::Private->value) {
                $validator->errors()->add('is_anonymous', __('media::validation.anonymous_private'));
            }

            if ($this->has('tags')) {
                $this->validateTagInput($validator);
            }
        });
    }
}
