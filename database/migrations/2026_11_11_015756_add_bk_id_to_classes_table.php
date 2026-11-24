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
        if (!Schema::hasColumn('classes', 'bk_id')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->foreignId('bk_id')
                    ->nullable()
                    ->after('section')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        // Mapping 48 Kelas ke 5 Guru BK sesuai SK Kepala Sekolah SMK N 1 Bantul (Pilihan A)
        $bkAssignments = [
            'candra_rahadian' => [
                'X AKL 1', 'X AKL 2', 'X AKL 3', 'X TKJ 2',
                'XI AKL 3', 'XI LPS 2', 'XI TKJ 2',
                'XII AKL 4', 'XII LPS 1', 'XII TKJ 2',
            ],
            'nurhidayat_sulistiawan' => [
                'X LPS 1', 'X TKJ 1', 'X DKV 1', 'X DKV 2',
                'XI TKJ 1', 'XI DKV 1', 'XI DKV 2',
                'XII TKJ 1', 'XII DKV 1', 'XII DKV 2',
            ],
            'ryawidyaningtyas' => [
                'X LPS 2', 'X RPL 1', 'X RPL 2',
                'XI RPL 1', 'XI RPL 2',
                'XII AKL 1', 'XII AKL 2', 'XII RPL 1', 'XII RPL 2',
            ],
            'aisyah_wulandari' => [
                'X MP 1', 'X MP 2',
                'XI AKL 1', 'XI AKL 2', 'XI LPS 1', 'XI MP 1', 'XI MP 2',
                'XII AKL 3', 'XII MP 1', 'XII MP 2',
            ],
            'alivi_sazkia' => [
                'X BR 1', 'X BR 2', 'X BD 1',
                'XI BR 1', 'XI BR 2', 'XI BD 1',
                'XII BR 1', 'XII BR 2', 'XII BD 1',
            ],
        ];

        foreach ($bkAssignments as $username => $classNames) {
            $user = DB::table('users')->where('username', $username)->first();
            if ($user) {
                DB::table('classes')
                    ->whereIn('class_name', $classNames)
                    ->update(['bk_id' => $user->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('classes', 'bk_id')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->dropForeign(['bk_id']);
                $table->dropColumn('bk_id');
            });
        }
    }
};
