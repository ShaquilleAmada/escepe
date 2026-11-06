<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Roles
        $adminRole = Role::create(['role_name' => 'Admin']);
        $guruRole = Role::create(['role_name' => 'Guru']);
        $siswaRole = Role::create(['role_name' => 'Siswa']);
        $orangTuaRole = Role::create(['role_name' => 'OrangTua']);
        $bkRole = Role::create(['role_name' => 'BK']);
        $waliKelasRole = Role::create(['role_name' => 'WaliKelas']);

        // 2. Seed Default Admin User
        $adminUser = User::create([
            'role_id' => $adminRole->id,
            'username' => 'admin',
            'password' => Hash::make('admin123'),
            'fullname' => 'Administrator Sistem SCP',
            'email' => 'admin@admin.com',
        ]);

        // 3. Seed Default System Configuration
        \App\Models\SystemConfiguration::create([
            'active_cycle' => 'Putaran 1',
            'is_schedule_locked' => false,
            'updated_by' => $adminUser->id,
        ]);

        // 4. Seed Classes (Hanya XII RPL 1 dan XII RPL 2 jika belum ada)
        $class1 = \App\Models\Classes::firstOrCreate(['class_name' => 'XII RPL 1'], ['grade_level' => 'XII', 'major' => 'RPL', 'section' => '1']);
        $class2 = \App\Models\Classes::firstOrCreate(['class_name' => 'XII RPL 2'], ['grade_level' => 'XII', 'major' => 'RPL', 'section' => '2']);

        // 5. Seed Rooms
        $room1 = \App\Models\Room::firstOrCreate(['room_name' => 'Lab 16']);
        $room2 = \App\Models\Room::firstOrCreate(['room_name' => 'Lab 19']);
        $room3 = \App\Models\Room::firstOrCreate(['room_name' => 'Ruang 10']);

        $this->call([
            UserSeeder::class,
            ScheduleSeeder::class,
            StudentSeeder::class,
            RealisticTestSeeder::class,
        ]);
    }
}
