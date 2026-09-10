<?php

declare(strict_types=1);

namespace Modules\Media\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Media\Enums\VoteValue;

/**
 * One endpoint covers all three transitions, because they are one gesture:
 * 1 / -1 casts or switches, 0 withdraws. Authorization happens in the
 * controller against the resolved item, not here — the item has to be looked
 * up through visibleTo() first, so that an item the viewer cannot see 404s
 * rather than 403s.
 */
final class CastVoteRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'value' => ['required', 'integer', 'in:1,-1,0'],
        ];
    }

    /**
     * The requested vote, or null for a withdrawal.
     */
    public function voteValue(): ?VoteValue
    {
        $value = $this->integer('value');

        return $value === 0 ? null : VoteValue::from($value);
    }
}
