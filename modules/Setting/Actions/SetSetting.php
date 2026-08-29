<?php

declare(strict_types=1);

namespace Modules\Setting\Actions;

use App\Contracts\SettingRegistry;
use Illuminate\Support\Facades\Validator;
use Modules\Setting\SettingManager;

/**
 * The single write path for a setting. Validation lives here rather than in a
 * FormRequest so that HTTP, console and any future importer enforce the same
 * rules, and so the rule sits next to the declaration.
 */
final class SetSetting
{
    public function __construct(
        private readonly SettingRegistry $registry,
        private readonly SettingManager $settings,
    ) {}

    public function handle(string $key, mixed $value): void
    {
        $this->validate($key, $value);

        $this->settings->set($key, $value);
    }

    /**
     * Runs the same validation `handle()` applies before writing, without
     * writing. Lets a caller that writes several keys at once (e.g.
     * `UpdateSystemSettingsController`) validate every submitted key/value
     * pair up front and only write once all of them pass, so a single
     * invalid key can't leave an earlier key's write in place.
     */
    public function validate(string $key, mixed $value): void
    {
        $definition = $this->registry->get($key);

        if ($definition->validationRules !== []) {
            // The key is the field name so a form can attach the message to
            // the right input. Dots are escaped: they are not nesting here.
            $field = str_replace('.', '\.', $key);

            Validator::make(
                [$key => $value],
                [$field => $definition->validationRules],
            )->validate();
        }
    }
}
