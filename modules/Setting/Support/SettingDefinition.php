<?php

declare(strict_types=1);

namespace Modules\Setting\Support;

use BackedEnum;
use Illuminate\Validation\Rule;
use JsonException;

/**
 * The declaration of one setting: what type it is, what it defaults to, whether
 * it is stored encrypted, and how a submitted value is validated.
 *
 * The only place the type mapping between the stored JSON string and the value
 * the application sees lives.
 */
final class SettingDefinition
{
    /**
     * @param  'string'|'bool'|'int'|'array'|'enum'  $type
     * @param  class-string<BackedEnum>|null  $enumClass
     * @param  list<mixed>  $validationRules
     */
    private function __construct(
        public readonly string $type,
        public readonly mixed $default,
        public readonly ?string $enumClass = null,
        public readonly bool $isEncrypted = false,
        public readonly array $validationRules = [],
    ) {}

    public static function string(string $default = ''): self
    {
        return new self('string', $default);
    }

    public static function bool(bool $default = false): self
    {
        return new self('bool', $default, validationRules: ['boolean']);
    }

    public static function int(int $default = 0): self
    {
        return new self('int', $default);
    }

    /**
     * @param  array<array-key, mixed>  $default
     */
    public static function array(array $default = []): self
    {
        return new self('array', $default);
    }

    /**
     * The default is given in the enum's backing form, because it usually comes
     * from env(), which yields strings.
     *
     * @param  class-string<BackedEnum>  $enumClass
     */
    public static function enum(string $enumClass, string|int $default): self
    {
        return new self('enum', $default, $enumClass, validationRules: [Rule::enum($enumClass)]);
    }

    public function encrypted(): self
    {
        return new self($this->type, $this->default, $this->enumClass, true, $this->validationRules);
    }

    /**
     * @param  list<mixed>  $rules
     */
    public function rules(array $rules): self
    {
        return new self($this->type, $this->default, $this->enumClass, $this->isEncrypted, $rules);
    }

    /**
     * Turn the stored JSON string into the value the application sees. A null
     * row, malformed JSON or a value the type no longer accepts all fall back
     * to the default: a bad row must not take the site down.
     */
    public function cast(?string $raw): mixed
    {
        if ($raw === null) {
            return $this->coerce($this->default) ?? $this->default;
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->coerce($this->default) ?? $this->default;
        }

        return $this->coerce($decoded) ?? $this->coerce($this->default) ?? $this->default;
    }

    public function serialize(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        return (string) json_encode($value);
    }

    /**
     * Null means "this value is not usable for this type" — the caller falls
     * back to the default.
     */
    private function coerce(mixed $value): mixed
    {
        return match ($this->type) {
            'string' => is_scalar($value) ? (string) $value : null,
            'bool' => is_bool($value) ? $value : (is_scalar($value) ? filter_var($value, FILTER_VALIDATE_BOOL) : null),
            'int' => is_numeric($value) ? (int) $value : null,
            'array' => is_array($value) ? $value : null,
            'enum' => $this->toEnum($value),
        };
    }

    private function toEnum(mixed $value): ?BackedEnum
    {
        if ($value instanceof BackedEnum) {
            return $value;
        }

        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        /** @var class-string<BackedEnum> $enum */
        $enum = $this->enumClass;

        return $enum::tryFrom($value);
    }
}
