<?php

declare(strict_types=1);

namespace Modules\Tag\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class TagCategoryRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }
}
