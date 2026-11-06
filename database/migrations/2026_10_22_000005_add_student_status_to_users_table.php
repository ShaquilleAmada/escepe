<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('student_status', 20)->default('aktif')->after('class_id');
            $table->string('graduation_year', 20)->nullable()->after('student_status');
            $table->string('last_class_name', 50)->nullable()->after('graduation_year');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['student_status', 'graduation_year', 'last_class_name']);
        });
    }
};
