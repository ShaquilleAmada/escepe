<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Akun Admin / Waka Kurikulum (Role ID 1)
        User::updateOrCreate(
            ['username' => 'waka_kurikulum'],
            [
                'role_id' => 1,
                'password' => Hash::make('password123'),
                'fullname' => 'Waka Kurikulum',
                'email' => 'missinghumanss@gmail.com',
            ]
        );

        // 2. Akun Guru Kejuruan (RPL) - Role ID 2
        User::updateOrCreate(
            ['username' => 'diah_utaminingsih'],
            [
                'role_id' => 2,
                'password' => Hash::make('password123'),
                'fullname' => 'DIAH UTAMININGSIH, S.T',
            ]
        );

        User::updateOrCreate(
            ['username' => 'reny_murni'],
            [
                'role_id' => 2,
                'password' => Hash::make('password123'),
                'fullname' => 'RENY MURNI HIDAYATI, S.Pd',
            ]
        );

        User::updateOrCreate(
            ['username' => 'dinda_ayu'],
            [
                'role_id' => 2,
                'password' => Hash::make('password123'),
                'fullname' => 'DINDA AYU DEWANTI, S.Kom',
            ]
        );

        User::updateOrCreate(
            ['username' => 'rosyida_ramadhani'],
            [
                'role_id' => 2,
                'password' => Hash::make('password123'),
                'fullname' => 'ROSYIDA RAMADHANI, S.Pd',
            ]
        );

        User::updateOrCreate(
            ['username' => 'rusdiyanto'],
            [
                'role_id' => 2,
                'password' => Hash::make('password123'),
                'fullname' => 'RUSDIYANTO, S.Pd',
            ]
        );

        // 3. Akun Guru Normada (Umum) - Role ID 2
        User::updateOrCreate(
            ['username' => 'dwi_lesmiyati'],
            [
                'role_id' => 2,
                'password' => Hash::make('password123'),
                'fullname' => 'DWI LESMIYATI, S.Pd.',
            ]
        );

        User::updateOrCreate(
            ['username' => 'sujar_hartono'],
            [
                'role_id' => 2,
                'password' => Hash::make('password123'),
                'fullname' => 'SUJAR HARTONO, S.Pd.',
            ]
        );

        User::updateOrCreate(
            ['username' => 'heru_purnomo'],
            [
                'role_id' => 2,
                'password' => Hash::make('password123'),
                'fullname' => 'MUHAMMAD HERU PURNOMO, M.S.I',
            ]
        );

        User::updateOrCreate(
            ['username' => 'yulita_winajeng'],
            [
                'role_id' => 2,
                'password' => Hash::make('password123'),
                'fullname' => 'YULITA EKA WINAJENG, S.Pd.',
            ]
        );

        User::updateOrCreate(
            ['username' => 'novia_rahmalina'],
            [
                'role_id' => 2,
                'password' => Hash::make('password123'),
                'fullname' => 'NOVIA RAHMALINA, S.Pd.',
            ]
        );

        User::updateOrCreate(
            ['username' => 'widodo'],
            [
                'role_id' => 2,
                'password' => Hash::make('password123'),
                'fullname' => 'WIDODO, S.Pd.',
            ]
        );

        User::updateOrCreate(
            ['username' => 'haris_budiawan'],
            [
                'role_id' => 2,
                'password' => Hash::make('password123'),
                'fullname' => 'HARIS BUDIAWAN, S.Pd.',
            ]
        );

        // 4. Akun Guru BK (Role ID 5 - Tim Kesiswaan)
        User::updateOrCreate(
            ['username' => 'ryawidyaningtyas'],
            [
                'role_id' => 5,
                'password' => Hash::make('password123'),
                'fullname' => 'RYAWIDYANINGTYAS, S.Pd.',
            ]
        );
    }
}
