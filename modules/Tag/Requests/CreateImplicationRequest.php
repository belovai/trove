<?php

declare(strict_types=1);

namespace Modules\Tag\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateImplicationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'implies' => ['required', 'string', 'exists:tags,name'],
        ];
    }
}
