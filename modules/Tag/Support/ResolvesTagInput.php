<?php

declare(strict_types=1);

namespace Modules\Tag\Support;

use Illuminate\Contracts\Validation\Validator;
use Modules\Tag\DataObjects\TagInputResult;
use Modules\Tag\Exceptions\InvalidTagName;
use Modules\Tag\Exceptions\UnknownTagCategory;
use Modules\Tag\Services\TagResolver;

/**
 * Mixed into any form request that accepts a `tags` array. Validation only
 * checks the input, so a bad name becomes a field error rather than a 500;
 * the tags themselves are created when the controller asks for them, once
 * every other rule has passed. The result is memoized so the controller does
 * not resolve twice.
 */
trait ResolvesTagInput
{
    private ?TagInputResult $resolvedTags = null;

    /**
     * Call from withValidator()'s after() callback.
     */
    public function validateTagInput(Validator $validator): void
    {
        if ($validator->errors()->has('tags')) {
            return;
        }

        try {
            app(TagResolver::class)->validate($this->rawTagInput());
        } catch (InvalidTagName|UnknownTagCategory $e) {
            $validator->errors()->add('tags', $e->translated());
        }
    }

    /**
     * Only valid after validation has passed: this is where missing tags are
     * created, so calling it commits them.
     */
    public function resolvedTags(): TagInputResult
    {
        return $this->resolvedTags ??= app(TagResolver::class)->resolve($this->rawTagInput());
    }

    /**
     * @return list<string>
     */
    private function rawTagInput(): array
    {
        return array_values(array_map(
            static fn (mixed $tag): string => (string) $tag,
            (array) $this->input('tags', []),
        ));
    }
}
