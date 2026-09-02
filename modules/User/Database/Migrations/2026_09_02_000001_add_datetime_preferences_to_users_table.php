<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * All three are nullable: null means "use the system default", which lives
     * in the settings table (`app.timezone`, `app.date_format`, `app.time_format`).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('timezone')->nullable()->after('locale');
            $table->string('date_format')->nullable()->after('timezone');
            $table->string('time_format')->nullable()->after('date_format');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['timezone', 'date_format', 'time_format']);
        });
    }
};
