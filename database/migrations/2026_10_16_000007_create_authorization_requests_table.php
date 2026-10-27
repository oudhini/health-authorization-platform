<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authorization_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('insurer_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('beneficiary_id')->constrained()->restrictOnDelete();
            $table->foreignId('policy_id')->constrained()->restrictOnDelete();
            // Exam request linked to the consultation request it follows (workflow 2).
            $table->foreignId('related_request_id')->nullable()->constrained('authorization_requests')->restrictOnDelete();
            // App\Domain\Requests\RequestStatus
            $table->string('status', 20)->default('draft');
            // Copy of the policy rate at creation: a later policy change must not alter this request.
            $table->unsignedTinyInteger('coverage_rate');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->restrictOnDelete();
            // Rejection reason or message asked of the facility by the insurer.
            $table->text('decision_note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            // Both portals list requests by organization and status.
            $table->index(['facility_id', 'status']);
            $table->index(['insurer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authorization_requests');
    }
};
