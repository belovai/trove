<?php

declare(strict_types=1);

namespace Modules\Tag\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateAliasRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'alias' => ['required', 'string', 'max:255'],
        ];
    }
}
