<?php

declare(strict_types=1);

namespace Modules\Tag\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Tag\DataObjects\TagName;
use Modules\Tag\Exceptions\InvalidTagName;

final class UpdateTagRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:tag_categories,id'],
            'description' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('name')) {
                return;
            }

            try {
                TagName::from($this->string('name')->value());
            } catch (InvalidTagName $e) {
                $validator->errors()->add('name', $e->translated());
            }
        });
    }
}
