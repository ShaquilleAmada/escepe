<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Bersihkan seluruh data permohonan izin (leave_requests) dummy/nyangkut
        if (Schema::hasTable('leave_requests')) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('leave_requests')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
