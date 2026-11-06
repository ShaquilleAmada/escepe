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
        // 1. Create majors table
        if (!Schema::hasTable('majors')) {
            Schema::create('majors', function (Blueprint $table) {
                $table->id();
                $table->string('major_code', 20)->unique();
                $table->string('major_name', 100);
                $table->timestamps();
            });

            // Seed majors data
            $now = now();
            $majors = [
                ['major_code' => 'RPL', 'major_name' => 'Rekayasa Perangkat Lunak'],
                ['major_code' => 'TKJ', 'major_name' => 'Teknik Komputer dan Jaringan'],
                ['major_code' => 'DKV', 'major_name' => 'Desain Komunikasi Visual'],
                ['major_code' => 'BR',  'major_name' => 'Bisnis Ritel'],
                ['major_code' => 'BD',  'major_name' => 'Bisnis Digital'],
                ['major_code' => 'LPS', 'major_name' => 'Layanan Perbankan Syariah'],
                ['major_code' => 'AKL', 'major_name' => 'Akuntansi dan Keuangan Lembaga'],
                ['major_code' => 'MP',  'major_name' => 'Manajemen Perkantoran'],
            ];

            foreach ($majors as $m) {
                DB::table('majors')->updateOrInsert(
                    ['major_code' => $m['major_code']],
                    [
                        'major_name' => $m['major_name'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('majors');
    }
};
