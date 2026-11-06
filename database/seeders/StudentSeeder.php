<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Classes;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $class12Rpl1 = Classes::where('class_name', 'XII RPL 1')->first();
        $class11Rpl1 = Classes::where('class_name', 'XI RPL 1')->first();
        $class10Rpl1 = Classes::where('class_name', 'X RPL 1')->first();
        $class10Akl1 = Classes::where('class_name', 'X AKL 1')->first();

        // 5 Siswa XII RPL 1 (NIS 5001-5005)
        $students12Rpl1 = [
            ['fullname' => 'Aditia Pratama', 'username' => '5001', 'nip_nisn' => '5001'],
            ['fullname' => 'Bagas Saputra', 'username' => '5002', 'nip_nisn' => '5002'],
            ['fullname' => 'Cantika Putri Maharani', 'username' => '5003', 'nip_nisn' => '5003'],
            ['fullname' => 'Dimas Wahyu Nugroho', 'username' => '5004', 'nip_nisn' => '5004'],
            ['fullname' => 'Eka Rahmawati', 'username' => '5005', 'nip_nisn' => '5005'],
        ];

        if ($class12Rpl1) {
            $this->seedStudentsAndParents($class12Rpl1, $students12Rpl1);
        }

        // 5 Siswa XI RPL 1 (NIS 5101-5105)
        $students11Rpl1 = [
            ['fullname' => 'Galang Danendra', 'username' => '5101', 'nip_nisn' => '5101'],
            ['fullname' => 'Hana Shalsabila', 'username' => '5102', 'nip_nisn' => '5102'],
            ['fullname' => 'Irfan Maulana', 'username' => '5103', 'nip_nisn' => '5103'],
            ['fullname' => 'Kirana Larasati', 'username' => '5104', 'nip_nisn' => '5104'],
            ['fullname' => 'Lukman Hakim', 'username' => '5105', 'nip_nisn' => '5105'],
        ];

        if ($class11Rpl1) {
            $this->seedStudentsAndParents($class11Rpl1, $students11Rpl1);
        }

        // 5 Siswa X RPL 1 (NIS 5201-5205)
        $students10Rpl1 = [
            ['fullname' => 'Muhammad Rizky', 'username' => '5201', 'nip_nisn' => '5201'],
            ['fullname' => 'Nabila Azzahra', 'username' => '5202', 'nip_nisn' => '5202'],
            ['fullname' => 'Oki Setiawan', 'username' => '5203', 'nip_nisn' => '5203'],
            ['fullname' => 'Putri Wulandari', 'username' => '5204', 'nip_nisn' => '5204'],
            ['fullname' => 'Rafi Pratama', 'username' => '5205', 'nip_nisn' => '5205'],
        ];

        if ($class10Rpl1) {
            $this->seedStudentsAndParents($class10Rpl1, $students10Rpl1);
        }

        // 5 Siswa X AKL 1 (NIS 5301-5305)
        if ($class10Akl1) {
            $students10Akl1 = [
                ['fullname' => 'Ahmad Fauzan', 'username' => '5301', 'nip_nisn' => '5301'],
                ['fullname' => 'Bella Safitri', 'username' => '5302', 'nip_nisn' => '5302'],
                ['fullname' => 'Citra Lestari', 'username' => '5303', 'nip_nisn' => '5303'],
                ['fullname' => 'Dafi Ramadhan', 'username' => '5304', 'nip_nisn' => '5304'],
                ['fullname' => 'Elsa Febriani', 'username' => '5305', 'nip_nisn' => '5305'],
            ];
            $this->seedStudentsAndParents($class10Akl1, $students10Akl1);
        }
    }

    private function seedStudentsAndParents(Classes $class, array $studentsData): void
    {
        foreach ($studentsData as $s) {
            // 1. Akun Siswa (login dengan NIS)
            $student = User::updateOrCreate(
                ['username' => $s['username']],
                [
                    'role_id' => 3,
                    'class_id' => $class->id,
                    'student_status' => 'aktif',
                    'fullname' => $s['fullname'],
                    'nip_nisn' => $s['nip_nisn'],
                    'password' => Hash::make('password123'),
                ]
            );

            // 2. Akun Orang Tua Terpisah (login dengan 'P' + NIS)
            $parentUsername = 'P' . $s['nip_nisn'];
            $parent = User::updateOrCreate(
                ['username' => $parentUsername],
                [
                    'role_id' => 4, // OrangTua
                    'class_id' => $class->id,
                    'parent_id' => $student->id,
                    'fullname' => 'Orang Tua ' . $s['fullname'],
                    'password' => Hash::make('password123'),
                ]
            );

            $student->update(['parent_id' => $parent->id]);
        }
    }
}
