<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use App\Models\SystemConfiguration;
use Illuminate\Support\Facades\Hash;

class RealisticTestSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan Role ada
        $roles = [
            Role::ADMIN      => 'Admin',
            Role::GURU       => 'Guru',
            Role::SISWA      => 'Siswa',
            Role::ORANG_TUA  => 'OrangTua',
            Role::BK         => 'BK',
            Role::WALI_KELAS => 'WaliKelas',
        ];

        foreach ($roles as $id => $name) {
            Role::firstOrCreate(['id' => $id], ['role_name' => $name]);
        }

        // 1. Waka Kurikulum (Role 1) jika belum ada
        $waka = User::firstOrCreate(
            ['username' => 'waka_kurikulum'],
            [
                'role_id'  => Role::ADMIN,
                'fullname' => 'Waka Kurikulum SMK 1 Bantul',
                'email'    => 'missinghumanss@gmail.com',
                'password' => Hash::make('admin123'),
            ]
        );

        // 2. System Configuration
        SystemConfiguration::firstOrCreate(
            ['id' => 1],
            [
                'active_cycle'       => 'Putaran 1',
                'is_schedule_locked' => false,
                'academic_year'      => '2025/2026',
                'semester'           => 'Ganjil',
                'updated_by'         => $waka->id,
            ]
        );

        // Catatan: Seluruh data dummy pengujian (Budi, Dewi, Siti, Aditya 10001-10005)
        // telah dibersihkan agar master data menggunakan 100% data riil SMK Negeri 1 Bantul.
    }
}
