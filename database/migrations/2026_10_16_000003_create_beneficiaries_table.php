<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policyholder_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->date('birth_date');
            // 'self', 'spouse', 'child' or 'other'. Informative only for now (open question 4.4).
            $table->string('relationship', 20);
            // The identifier typed by the facility agent to find the beneficiary.
            $table->string('member_number', 50)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
