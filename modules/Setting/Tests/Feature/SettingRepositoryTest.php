<?php

declare(strict_types=1);

namespace Modules\Setting\Tests\Feature;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Setting\Models\Setting;
use Modules\Setting\Repositories\SettingRepository;
use ReflectionClass;
use RuntimeException;
use Tests\TestCase;

final class SettingRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_and_returns_rows_keyed_by_setting_key(): void
    {
        $repository = new SettingRepository;
        $repository->set('app.name', '"Trove"', false);

        $this->assertSame(
            ['app.name' => ['value' => '"Trove"', 'is_encrypted' => false]],
            $repository->all(),
        );
    }

    public function test_setting_the_same_key_twice_updates_rather_than_duplicates(): void
    {
        $repository = new SettingRepository;
        $repository->set('app.name', '"One"', false);
        $repository->set('app.name', '"Two"', false);

        $this->assertSame(1, DB::table('settings')->where('key', 'app.name')->count());
        $this->assertSame('"Two"', $repository->all()['app.name']['value']);
    }

    public function test_reads_are_cached_and_a_write_invalidates_the_cache(): void
    {
        $repository = new SettingRepository;
        $repository->set('app.name', '"One"', false);

        $repository->all();
        $this->assertTrue(Cache::has(SettingRepository::CACHE_KEY));

        DB::table('settings')->where('key', 'app.name')->update(['value' => '"Sneaky"']);
        // Still the cached copy — the cache is the point.
        $this->assertSame('"One"', $repository->all()['app.name']['value']);

        $repository->set('app.name', '"Two"', false);
        $this->assertSame('"Two"', $repository->all()['app.name']['value']);
    }

    public function test_forget_removes_the_row_and_invalidates_the_cache(): void
    {
        $repository = new SettingRepository;
        $repository->set('app.name', '"One"', false);
        $repository->all();

        $repository->forget('app.name');

        $this->assertSame([], $repository->all());
    }

    public function test_a_missing_table_yields_an_empty_map(): void
    {
        // Without this, a not-yet-migrated install cannot render even an error
        // page: the root Blade template reads app.name.
        Schema::drop('settings');
        Cache::forget(SettingRepository::CACHE_KEY);

        $this->assertSame([], (new SettingRepository)->all());
    }

    public function test_a_transient_read_failure_is_not_cached(): void
    {
        $repository = new SettingRepository;
        $repository->set('app.name', '"One"', false);
        Cache::forget(SettingRepository::CACHE_KEY);

        // Simulate a transient failure distinct from "table missing": the
        // table exists and is otherwise readable, but this one query blows
        // up (e.g. a momentary DB connection blip).
        Setting::addGlobalScope('test-transient-failure', function (Builder $builder): void {
            throw new RuntimeException('Simulated transient database failure.');
        });

        try {
            $this->assertSame([], $repository->all());
            $this->assertFalse(
                Cache::has(SettingRepository::CACHE_KEY),
                'a transient failure must not be cached',
            );
        } finally {
            // Remove the induced failure regardless of outcome so it can't
            // leak into another test in this file.
            (new ReflectionClass(Setting::class))->setStaticPropertyValue('globalScopes', []);
        }

        // A fresh call, with the failure gone, must read live data rather
        // than a permanently cached empty result.
        $this->assertSame(
            ['app.name' => ['value' => '"One"', 'is_encrypted' => false]],
            $repository->all(),
        );
    }
}
