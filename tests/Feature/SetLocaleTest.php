<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Modules\User\Models\User;
use Tests\TestCase;

final class SetLocaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/locale-probe', fn (): string => app()->getLocale());
    }

    public function test_it_falls_back_to_the_site_default(): void
    {
        $this->get('/locale-probe')->assertSee(config('app.locale'));
    }

    public function test_it_uses_a_supported_accept_language_header(): void
    {
        $this->get('/locale-probe', ['Accept-Language' => 'hu-HU,hu;q=0.9,en;q=0.8'])
            ->assertSee('hu');
    }

    public function test_it_ignores_an_unsupported_accept_language_header(): void
    {
        $this->get('/locale-probe', ['Accept-Language' => 'de-DE,de;q=0.9'])
            ->assertSee(config('app.locale'));
    }

    public function test_the_user_preference_wins_over_the_header(): void
    {
        $user = User::factory()->create(['locale' => 'hu']);

        $this->actingAs($user)
            ->get('/locale-probe', ['Accept-Language' => 'en-GB,en;q=0.9'])
            ->assertSee('hu');
    }
}
