<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_insurer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('insurer_id')->constrained('organizations')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['facility_id', 'insurer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_insurer');
    }
};
