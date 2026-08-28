<?php

declare(strict_types=1);

namespace Modules\Tag\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ImportTaxonomyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tag.admin') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240'],
            'replace' => ['boolean'],
        ];
    }
}
