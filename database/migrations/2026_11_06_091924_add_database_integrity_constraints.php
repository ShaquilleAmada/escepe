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
        Schema::table('rooms', function (Blueprint $table) {
            $table->unique('room_name', 'rooms_room_name_unique');
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->unique('class_name', 'classes_class_name_unique');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->unique(['journal_id', 'student_id'], 'attendances_journal_student_unique');
        });

        Schema::table('journals', function (Blueprint $table) {
            $table->unique(['schedule_id', 'date'], 'journals_schedule_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropUnique('rooms_room_name_unique');
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->dropUnique('classes_class_name_unique');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique('attendances_journal_student_unique');
        });

        Schema::table('journals', function (Blueprint $table) {
            $table->dropUnique('journals_schedule_date_unique');
        });
    }
};
