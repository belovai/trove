<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Unit;

use Modules\Media\Enums\SafetyRating;
use Tests\TestCase;

final class SafetyRatingTest extends TestCase
{
    public function test_ratings_are_ordered(): void
    {
        $this->assertTrue(SafetyRating::Safe->level() < SafetyRating::Sketchy->level());
        $this->assertTrue(SafetyRating::Sketchy->level() < SafetyRating::Unsafe->level());
    }

    public function test_a_viewer_filter_admits_everything_at_or_below_it(): void
    {
        $this->assertTrue(SafetyRating::Safe->isWithin(SafetyRating::Sketchy));
        $this->assertTrue(SafetyRating::Sketchy->isWithin(SafetyRating::Sketchy));
        $this->assertFalse(SafetyRating::Unsafe->isWithin(SafetyRating::Sketchy));
    }

    public function test_a_threshold_expands_to_every_rating_at_or_below_it(): void
    {
        $this->assertSame([SafetyRating::Safe], SafetyRating::upTo(SafetyRating::Safe));

        $this->assertSame(
            [SafetyRating::Safe, SafetyRating::Sketchy],
            SafetyRating::upTo(SafetyRating::Sketchy),
        );

        $this->assertSame(
            [SafetyRating::Safe, SafetyRating::Sketchy, SafetyRating::Unsafe],
            SafetyRating::upTo(SafetyRating::Unsafe),
        );
    }
}
