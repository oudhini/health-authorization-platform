<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('authorization_request_id')->constrained()->restrictOnDelete();
            $table->string('type', 30);
            $table->string('original_name');
            // Path inside the PRIVATE disk, never a public URL.
            $table->string('stored_path');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_bytes');
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
