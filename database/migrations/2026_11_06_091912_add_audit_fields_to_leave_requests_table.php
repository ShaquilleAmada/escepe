<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_requests', 'leave_type')) {
                $table->enum('leave_type', ['izin', 'sakit'])->default('izin')->after('applicant_id');
            }
            if (!Schema::hasColumn('leave_requests', 'is_bypass')) {
                $table->boolean('is_bypass')->default(false)->after('verified_by');
            }
            if (!Schema::hasColumn('leave_requests', 'decided_at')) {
                $table->timestamp('decided_at')->nullable()->after('is_bypass');
            }
            if (!Schema::hasColumn('leave_requests', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('reason');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['leave_type', 'is_bypass', 'decided_at', 'rejection_reason']);
        });
    }
};
