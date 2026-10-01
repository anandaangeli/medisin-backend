<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('no_rm', 20)->unique();   // RM0001, RM0002, ...
            $table->string('name', 100);
            $table->text('nik');                     // encrypted at rest (Laravel encrypted cast)
            $table->string('nik_hash', 64)->unique(); // sha256(nik) so duplicates can still be rejected
            $table->text('address');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
