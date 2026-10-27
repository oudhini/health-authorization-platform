<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            // 'facility' or 'insurer' (App\Enums\OrganizationType)
            $table->string('type', 20)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
