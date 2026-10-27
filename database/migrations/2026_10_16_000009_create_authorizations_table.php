<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authorizations', function (Blueprint $table) {
            $table->id();
            // unique() must come BEFORE constrained(): one authorization per request.
            $table->foreignId('authorization_request_id')->unique()->constrained()->restrictOnDelete();
            $table->date('valid_from');
            $table->date('valid_until');
            // Totals are frozen at approval: an approved authorization is never modified.
            $table->unsignedInteger('billed_total');
            $table->unsignedInteger('insurer_total');
            $table->unsignedInteger('patient_total');
            $table->unsignedInteger('overrun_total');
            $table->foreignId('decided_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authorizations');
    }
};
