<?php

declare(strict_types=1);

namespace Modules\Setting\Tests\Feature;

use App\Contracts\SettingRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Setting\Exceptions\UnknownSettingException;
use Modules\Setting\Facades\Settings;
use Modules\Setting\Repositories\SettingRepository;
use Modules\Setting\Support\SettingDefinition;
use Tests\TestCase;

final class SettingManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(SettingRegistry::class)->register('testing', [
            'testing.name' => SettingDefinition::string('default name'),
            'testing.secret' => SettingDefinition::string('')->encrypted(),
            'testing.count' => SettingDefinition::int(3),
        ]);
    }

    public function test_a_key_with_no_row_returns_its_default(): void
    {
        $this->assertSame('default name', Settings::get('testing.name'));
        $this->assertSame(3, Settings::get('testing.count'));
    }

    public function test_a_stored_value_wins_over_the_default(): void
    {
        Settings::set('testing.name', 'stored name');

        $this->assertSame('stored name', Settings::get('testing.name'));
    }

    public function test_forget_restores_the_default(): void
    {
        Settings::set('testing.name', 'stored name');
        Settings::forget('testing.name');

        $this->assertSame('default name', Settings::get('testing.name'));
    }

    public function test_an_unknown_key_throws(): void
    {
        $this->expectException(UnknownSettingException::class);

        Settings::get('testing.nope');
    }

    public function test_an_encrypted_setting_is_stored_as_ciphertext(): void
    {
        Settings::set('testing.secret', 'hunter2');

        $raw = DB::table('settings')->where('key', 'testing.secret')->first();

        $this->assertTrue((bool) $raw->is_encrypted);
        $this->assertStringNotContainsString('hunter2', (string) $raw->value);
        $this->assertSame('hunter2', Settings::get('testing.secret'));
    }

    public function test_an_undecryptable_value_falls_back_to_the_default_and_warns(): void
    {
        // Stands in for an APP_KEY rotation. A 500 here would lock an
        // administrator out of the page where they would fix it.
        Log::spy();

        Settings::set('testing.secret', 'hunter2');
        DB::table('settings')->where('key', 'testing.secret')->update(['value' => 'not-a-ciphertext']);
        // flush() clears the manager's per-request memo; the repository's cache
        // entry is separate and would still hold the valid ciphertext.
        Cache::forget(SettingRepository::CACHE_KEY);
        Settings::flush();

        $this->assertSame('', Settings::get('testing.secret'));
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_a_namespace_returns_cast_values_for_its_keys(): void
    {
        Settings::set('testing.count', 7);

        $this->assertSame(
            ['testing.name' => 'default name', 'testing.secret' => '', 'testing.count' => 7],
            Settings::namespace('testing'),
        );
    }

    public function test_repeated_reads_hit_the_repository_once(): void
    {
        Settings::set('testing.name', 'stored name');
        Settings::flush();

        DB::enableQueryLog();
        Settings::get('testing.name');
        Settings::get('testing.count');
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(1, count($queries));
    }
}
