<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $levels = ['X', 'XI', 'XII'];
        $majors = ['RPL', 'TKJ', 'DKV', 'BR', 'BD', 'LPS', 'AKL', 'MP'];
        $sections = ['1', '2', '3', '4'];
        $now = now();

        foreach ($levels as $level) {
            foreach ($majors as $major) {
                foreach ($sections as $section) {
                    $className = "{$level} {$major} {$section}";

                    $existing = DB::table('classes')->where('class_name', $className)->first();

                    if (!$existing) {
                        DB::table('classes')->insert([
                            'class_name' => $className,
                            'grade_level' => $level,
                            'major' => $major,
                            'section' => $section,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    } else {
                        DB::table('classes')->where('id', $existing->id)->update([
                            'grade_level' => $level,
                            'major' => $major,
                            'section' => $section,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep ID 1 and 2, remove other auto-seeded classes if rolled back
        DB::table('classes')->whereNotIn('id', [1, 2])->delete();
    }
};
