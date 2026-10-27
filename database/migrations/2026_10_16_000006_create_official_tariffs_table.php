<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_tariffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_act_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('amount');
            $table->date('effective_from');
            $table->timestamps();

            // One tariff per act and per start date; also serves the "latest tariff at date X" lookup.
            $table->unique(['medical_act_id', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_tariffs');
    }
};
