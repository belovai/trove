<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Deliberately not seeded. A missing row means the declared default
        // applies, so a fresh install has an empty table and correct
        // behaviour; the table only ever holds values an administrator
        // actually changed.
        Schema::create('settings', function (Blueprint $table) {
            // 191 keeps the unique index inside MySQL's utf8mb4 index limit.
            $table->id();
            $table->string('key', 191)->unique();
            $table->text('value')->nullable();
            // On the row, not only in the definition: decryption must not
            // depend on the registry, and rows written under an older
            // definition stay readable if a key's flag ever changes.
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
