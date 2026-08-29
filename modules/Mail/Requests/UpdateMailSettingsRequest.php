<?php

declare(strict_types=1);

namespace Modules\Mail\Requests;

use App\Contracts\SettingRegistry;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The allowlist is the "mail" namespace of the setting registry rather than a
 * literal list: an adapter added later brings its keys with it.
 */
final class UpdateMailSettingsRequest extends FormRequest
{
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
                if (!in_array($key, $this->allowedKeys(), true)) {
                    $validator->errors()->add($key, 'This setting cannot be changed here.');
                }
            }
        });
    }

    /**
     * The submitted subset. An empty string for an encrypted key means "leave
     * it alone": the form never receives the stored secret, so it cannot send
     * it back, and an untouched password field would otherwise clear it.
     * Laravel's ConvertEmptyStringsToNull middleware turns that empty string
     * into null before it reaches here, so both are treated as "not sent".
     *
     * @return array<string, mixed>
     */
    public function submitted(): array
    {
        $registry = app(SettingRegistry::class);
        $submitted = array_intersect_key($this->all(), array_flip($this->allowedKeys()));

        return array_filter(
            $submitted,
            fn (mixed $value, string $key): bool => !($registry->get($key)->isEncrypted && ($value === '' || $value === null)),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /** @return list<string> */
    private function allowedKeys(): array
    {
        return array_keys(app(SettingRegistry::class)->namespace('mail'));
    }
}
