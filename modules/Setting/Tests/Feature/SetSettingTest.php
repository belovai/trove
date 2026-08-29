<?php

declare(strict_types=1);

namespace Modules\Setting\Tests\Feature;

use App\Contracts\SettingRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\Setting\Actions\SetSetting;
use Modules\Setting\Facades\Settings;
use Modules\Setting\Support\SettingDefinition;
use Tests\TestCase;

final class SetSettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(SettingRegistry::class)->register('testing', [
            'testing.name' => SettingDefinition::string('default')->rules(['required', 'string', 'max:5']),
        ]);
    }

    public function test_a_valid_value_is_stored(): void
    {
        $this->app->make(SetSetting::class)->handle('testing.name', 'ok');

        $this->assertSame('ok', Settings::get('testing.name'));
    }

    public function test_an_invalid_value_is_rejected_and_nothing_is_stored(): void
    {
        try {
            $this->app->make(SetSetting::class)->handle('testing.name', 'far too long');
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('testing.name', $exception->errors());
        }

        $this->assertSame('default', Settings::get('testing.name'));
    }
}
