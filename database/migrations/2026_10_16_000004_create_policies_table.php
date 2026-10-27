<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('insurer_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('subscriber_id')->constrained()->restrictOnDelete();
            // Percentage 0-100, validated by the domain (CoverageCalculator) and by forms.
            $table->unsignedTinyInteger('coverage_rate');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->timestamps();

            // "Which policies are active for this subscriber?" is asked on every new request.
            $table->index(['subscriber_id', 'starts_on', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policies');
    }
};
