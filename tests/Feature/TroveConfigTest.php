<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class TroveConfigTest extends TestCase
{
    public function test_it_ships_english_and_hungarian_locales(): void
    {
        $this->assertSame(['en', 'hu'], config('trove.locales'));
    }
}
