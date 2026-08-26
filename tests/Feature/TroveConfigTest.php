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

    public function test_registration_is_open_with_an_optional_email_by_default(): void
    {
        $this->assertSame('open', config('trove.registration.mode'));
        $this->assertSame('optional', config('trove.registration.email'));
        $this->assertFalse(config('trove.registration.approval'));
    }
}
