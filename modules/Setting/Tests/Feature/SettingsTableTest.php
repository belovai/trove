<?php

declare(strict_types=1);

namespace Modules\Setting\Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Setting\Models\Setting;
use Tests\TestCase;

final class SettingsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_setting_row_can_be_stored_and_read_back(): void
    {
        Setting::query()->create([
            'key' => 'registration.mode',
            'value' => '"closed"',
            'is_encrypted' => false,
        ]);

        $row = Setting::query()->where('key', 'registration.mode')->firstOrFail();

        $this->assertSame('"closed"', $row->value);
        $this->assertFalse($row->is_encrypted);
    }

    public function test_the_key_is_unique(): void
    {
        Setting::query()->create(['key' => 'app.name', 'value' => '"A"', 'is_encrypted' => false]);

        $this->expectException(QueryException::class);

        Setting::query()->create(['key' => 'app.name', 'value' => '"B"', 'is_encrypted' => false]);
    }

    public function test_the_value_may_be_null(): void
    {
        $row = Setting::query()->create(['key' => 'mail.smtp.host', 'value' => null, 'is_encrypted' => false]);

        $this->assertNull($row->fresh()->value);
    }
}
