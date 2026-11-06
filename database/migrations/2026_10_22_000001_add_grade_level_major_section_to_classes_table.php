<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->string('grade_level', 10)->nullable()->after('class_name');
            $table->string('major', 20)->nullable()->after('grade_level');
            $table->string('section', 10)->nullable()->after('major');
        });

        // Auto-populate existing data from class_name (e.g., 'XII RPL 1')
        $classes = DB::table('classes')->get();
        foreach ($classes as $class) {
            $parts = preg_split('/\s+/', trim($class->class_name));
            $gradeLevel = $parts[0] ?? null;
            $major = isset($parts[1]) ? strtoupper($parts[1]) : null;
            $section = $parts[2] ?? null;

            DB::table('classes')->where('id', $class->id)->update([
                'grade_level' => $gradeLevel,
                'major' => $major,
                'section' => $section,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn(['grade_level', 'major', 'section']);
        });
    }
};
