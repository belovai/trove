<?php

declare(strict_types=1);

namespace Modules\Setting\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Enums\RegistrationEmailPolicy;
use Modules\Auth\Enums\RegistrationMode;
use Modules\Setting\Facades\Settings;
use Tests\TestCase;

final class DeclaredSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_shipped_defaults_match_the_previous_config_defaults(): void
    {
        $this->assertSame(RegistrationMode::Open, Settings::get('registration.mode'));
        $this->assertSame(RegistrationEmailPolicy::Optional, Settings::get('registration.email'));
        $this->assertFalse(Settings::get('registration.approval'));
        $this->assertSame('Trove', Settings::get('app.name'));
    }

    public function test_the_registration_namespace_holds_exactly_its_three_keys(): void
    {
        $this->assertSame(
            ['registration.mode', 'registration.email', 'registration.approval'],
            array_keys(Settings::namespace('registration')),
        );
    }
}
