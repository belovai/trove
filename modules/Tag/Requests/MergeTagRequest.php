<?php

declare(strict_types=1);

namespace Modules\Tag\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class MergeTagRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // No `different` rule: the tag being merged is a route segment,
            // not an input field. MergeTags returns early on a self-merge.
            'into' => ['required', 'string', 'exists:tags,name'],
        ];
    }
}
