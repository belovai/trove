<?php

declare(strict_types=1);

namespace Modules\Setting\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateSystemSettingsRequest extends FormRequest
{
    /**
     * The keys this page owns. Anything else is rejected, so the endpoint
     * cannot be used to write settings from another namespace.
     *
     * @var list<string>
     */
    public const KEYS = [
        'app.name',
        'registration.mode',
        'registration.email',
        'registration.approval',
        'registration.verify',
        'media.default_visibility',
    ];

    public function authorize(): bool
    {
        return $this->user()?->can('setting.manage') ?? false;
    }

    /**
     * Per-value validation is the definition's job (see SetSetting). This only
     * decides which keys may be submitted at all.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            foreach (array_keys($this->all()) as $key) {
                if (!in_array($key, self::KEYS, true)) {
                    $validator->errors()->add($key, 'This setting cannot be changed here.');
                }
            }
        });
    }

    /**
     * The submitted subset, in allowlist order. Partial by design: only the
     * keys actually sent are written, matching PATCH /m/{hash_id}.
     *
     * @return array<string, mixed>
     */
    public function submitted(): array
    {
        return array_intersect_key(
            $this->all(),
            array_flip(self::KEYS),
        );
    }
}
