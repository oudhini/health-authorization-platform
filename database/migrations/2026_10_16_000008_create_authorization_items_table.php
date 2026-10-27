<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authorization_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('authorization_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medical_act_id')->constrained()->restrictOnDelete();
            // Amounts in FCFA, integers only (section 6.3). The official tariff is a COPY
            // of the tariff applied at creation: later tariff changes never alter old requests.
            $table->unsignedInteger('billed_amount');
            $table->unsignedInteger('official_tariff');
            $table->unsignedInteger('accepted_amount');
            $table->unsignedInteger('insurer_share');
            $table->unsignedInteger('patient_share');
            $table->unsignedInteger('overrun');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authorization_items');
    }
};
