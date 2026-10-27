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
        Schema::table('beneficiaries', function (Blueprint $table): void {
            $table->foreignId('insurer_id')->nullable()->after('policyholder_id')
                ->constrained('organizations')->restrictOnDelete();
        });

        DB::statement(
            'UPDATE beneficiaries SET insurer_id = ('
            .'SELECT subscribers.insurer_id FROM policyholders '
            .'INNER JOIN subscribers ON subscribers.id = policyholders.subscriber_id '
            .'WHERE policyholders.id = beneficiaries.policyholder_id)'
        );

        Schema::table('beneficiaries', function (Blueprint $table): void {
            $table->unsignedBigInteger('insurer_id')->nullable(false)->change();
        });

        Schema::table('beneficiaries', function (Blueprint $table): void {
            $table->dropUnique('beneficiaries_member_number_unique');
            $table->unique(['insurer_id', 'member_number']);
        });

        Schema::table('policies', function (Blueprint $table): void {
            $table->dropUnique('policies_number_unique');
            $table->unique(['insurer_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::table('policies', function (Blueprint $table): void {
            $table->dropUnique('policies_insurer_id_number_unique');
            $table->unique('number');
        });

        Schema::table('beneficiaries', function (Blueprint $table): void {
            $table->dropUnique('beneficiaries_insurer_id_member_number_unique');
            $table->unique('member_number');
            $table->dropConstrainedForeignId('insurer_id');
        });
    }
};
