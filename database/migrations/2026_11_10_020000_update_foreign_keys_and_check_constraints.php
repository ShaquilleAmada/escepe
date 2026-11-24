<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ubah Foreign Key schedules.teacher_id menjadi RESTRICT
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
            $table->foreign('teacher_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });

        // 2. Ubah Foreign Key attendances.student_id menjadi RESTRICT
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->foreign('student_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });

        // 3. Tambahkan DDL CHECK constraint pada tabel schedules
        DB::statement('ALTER TABLE schedules ADD CONSTRAINT chk_schedule_time CHECK (start_time < end_time)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Hapus CHECK constraint
        DB::statement('ALTER TABLE schedules DROP CONSTRAINT chk_schedule_time');

        // 2. Kembalikan Foreign Key attendances.student_id ke CASCADE
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->foreign('student_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        // 3. Kembalikan Foreign Key schedules.teacher_id ke CASCADE
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
            $table->foreign('teacher_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }
};
