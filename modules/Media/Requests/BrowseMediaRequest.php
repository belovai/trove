<?php

declare(strict_types=1);

namespace Modules\Media\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Media\DataObjects\BrowseFilters;
use Modules\Media\Enums\SafetyRating;
use Modules\User\Models\User;

/**
 * The browse listing's ad-hoc filters. Safety travels as a comma-separated
 * set (`?safety=safe,unsafe`) so the URL stays shareable; an explicit empty
 * set is a valid selection and lists nothing.
 */
final class BrowseMediaRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'safety' => ['nullable', 'array'],
            'safety.*' => [Rule::enum(SafetyRating::class)],
            'untagged' => ['nullable', 'boolean'],
            'unlisted' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $safety = $this->query('safety');

        if (is_string($safety)) {
            $this->merge([
                'safety' => array_values(array_filter(explode(',', $safety), fn (string $value): bool => $value !== '')),
            ]);
        }
    }

    /**
     * The filters that apply to this request, with the viewer's stored default
     * standing in for anything they did not select.
     */
    public function filters(): BrowseFilters
    {
        /** @var User|null $viewer */
        $viewer = $this->user();

        return new BrowseFilters(
            ratings: $this->safetyRatings()
                ?? SafetyRating::upTo($viewer->default_safety_filter ?? SafetyRating::Safe),
            untagged: $this->boolean('untagged'),
            unlisted: $this->boolean('unlisted'),
        );
    }

    /**
     * The requested rating set, or null when the viewer sent no selection and
     * their stored default should decide.
     *
     * @return list<SafetyRating>|null
     */
    private function safetyRatings(): ?array
    {
        if (!$this->has('safety')) {
            return null;
        }

        return array_values(array_map(
            fn (string $value): SafetyRating => SafetyRating::from($value),
            $this->array('safety'),
        ));
    }
}
