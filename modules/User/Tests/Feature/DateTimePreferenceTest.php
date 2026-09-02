<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Setting\Facades\Settings;
use Modules\User\Enums\DateFormat;
use Modules\User\Enums\TimeFormat;
use Modules\User\Models\User;
use Tests\TestCase;

final class DateTimePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_set_a_timezone_and_formats(): void
    {
        $user = User::factory()->create([
            'timezone' => null,
            'date_format' => null,
            'time_format' => null,
        ]);

        $this->actingAs($user)
            ->patch('/account', [
                'timezone' => 'Europe/Budapest',
                'date_format' => DateFormat::DayMonthYearDot->value,
                'time_format' => TimeFormat::Twelve->value,
            ])
            ->assertRedirect('/settings/account');

        $user->refresh();

        $this->assertSame('Europe/Budapest', $user->timezone);
        $this->assertSame(DateFormat::DayMonthYearDot, $user->date_format);
        $this->assertSame(TimeFormat::Twelve, $user->time_format);
    }

    public function test_an_empty_value_falls_back_to_the_system_default(): void
    {
        $user = User::factory()->create([
            'timezone' => 'Europe/Budapest',
            'date_format' => DateFormat::DayMonthYearDot,
            'time_format' => TimeFormat::Twelve,
        ]);

        $this->actingAs($user)->patch('/account', [
            'timezone' => '',
            'date_format' => '',
            'time_format' => '',
        ]);

        $user->refresh();

        $this->assertNull($user->timezone);
        $this->assertNull($user->date_format);
        $this->assertNull($user->time_format);
    }

    public function test_an_unknown_timezone_is_rejected(): void
    {
        $user = User::factory()->create(['timezone' => null]);

        $this->actingAs($user)
            ->patch('/account', ['timezone' => 'Mars/Olympus_Mons'])
            ->assertSessionHasErrors('timezone');

        $this->assertNull($user->fresh()->timezone);
    }

    public function test_an_unknown_date_format_is_rejected(): void
    {
        $user = User::factory()->create(['date_format' => null]);

        $this->actingAs($user)
            ->patch('/account', ['date_format' => 'nonsense'])
            ->assertSessionHasErrors('date_format');

        $this->assertNull($user->fresh()->date_format);
    }

    public function test_the_shared_formats_prop_prefers_the_users_own_preference(): void
    {
        Settings::set('app.timezone', 'UTC');
        Settings::set('app.date_format', DateFormat::Iso->value);
        Settings::set('app.time_format', TimeFormat::TwentyFour->value);

        $user = User::factory()->create([
            'timezone' => 'Europe/Budapest',
            'date_format' => DateFormat::MonthNameDayYear,
            'time_format' => TimeFormat::Twelve,
        ]);

        $this->actingAs($user)
            ->get('/settings/account')
            ->assertInertia(fn ($page) => $page
                ->where('formats.timezone', 'Europe/Budapest')
                ->where('formats.date', DateFormat::MonthNameDayYear->value)
                ->where('formats.time', TimeFormat::Twelve->value)
            );
    }

    public function test_a_guest_gets_the_system_defaults(): void
    {
        Settings::set('app.timezone', 'Europe/Budapest');
        Settings::set('app.date_format', DateFormat::DayMonthYearDot->value);
        Settings::set('app.time_format', TimeFormat::Twelve->value);

        $this->get('/')
            ->assertInertia(fn ($page) => $page
                ->where('formats.timezone', 'Europe/Budapest')
                ->where('formats.date', DateFormat::DayMonthYearDot->value)
                ->where('formats.time', TimeFormat::Twelve->value)
            );
    }

    public function test_an_administrator_can_change_the_system_defaults(): void
    {
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)
            ->patch('/settings/system', [
                'app.timezone' => 'Europe/Budapest',
                'app.date_format' => DateFormat::DayMonthYearDot->value,
                'app.time_format' => TimeFormat::Twelve->value,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Europe/Budapest', Settings::get('app.timezone'));
        $this->assertSame(DateFormat::DayMonthYearDot, Settings::get('app.date_format'));
        $this->assertSame(TimeFormat::Twelve, Settings::get('app.time_format'));
    }

    public function test_an_unknown_system_timezone_is_rejected(): void
    {
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)
            ->patch('/settings/system', ['app.timezone' => 'Mars/Olympus_Mons'])
            ->assertSessionHasErrors('app.timezone');

        $this->assertSame('UTC', Settings::get('app.timezone'));
    }
}
