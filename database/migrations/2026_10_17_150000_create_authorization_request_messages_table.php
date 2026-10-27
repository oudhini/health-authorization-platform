<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authorization_request_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('authorization_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('type', 30);
            $table->text('body');
            // For insurer requests, this lets resubmission distinguish newly attached
            // documents from files that were already part of the original submission.
            $table->unsignedInteger('document_count')->nullable();
            $table->timestamps();

            $table->index(['authorization_request_id', 'id']);
        });

        DB::table('authorization_requests')
            ->where('status', 'info_requested')
            ->whereNotNull('decision_note')
            ->whereNotNull('decided_by')
            ->orderBy('id')
            ->each(function (object $request): void {
                DB::table('authorization_request_messages')->insert([
                    'authorization_request_id' => $request->id,
                    'user_id' => $request->decided_by,
                    'type' => 'information_requested',
                    'body' => $request->decision_note,
                    'document_count' => DB::table('documents')
                        ->where('authorization_request_id', $request->id)
                        ->count(),
                    'created_at' => $request->updated_at,
                    'updated_at' => $request->updated_at,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('authorization_request_messages');
    }
};
