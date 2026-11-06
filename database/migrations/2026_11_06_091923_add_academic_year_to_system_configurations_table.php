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
        Schema::table('system_configurations', function (Blueprint $table) {
            if (!Schema::hasColumn('system_configurations', 'academic_year')) {
                $table->string('academic_year', 20)->default('2026/2027')->after('active_cycle');
            }
            if (!Schema::hasColumn('system_configurations', 'semester')) {
                $table->enum('semester', ['Ganjil', 'Genap'])->default('Ganjil')->after('academic_year');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_configurations', function (Blueprint $table) {
            $table->dropColumn(['academic_year', 'semester']);
        });
    }
};
