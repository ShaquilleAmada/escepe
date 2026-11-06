<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Classes;
use App\Models\Room;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ambil Data Kelas
        $classRpl1 = Classes::where('class_name', 'XII RPL 1')->firstOrFail();
        $classRpl2 = Classes::where('class_name', 'XII RPL 2')->firstOrFail();

        // 2. Ambil Data Ruangan
        $room10 = Room::where('room_name', 'Ruang 10')->firstOrFail();
        $lab16 = Room::where('room_name', 'Lab 16')->firstOrFail();
        $lab19 = Room::where('room_name', 'Lab 19')->firstOrFail();

        // 3. Ambil Guru berdasarkan username (paling akurat)
        $teachers = [
            'novia' => User::where('username', 'novia_rahmalina')->firstOrFail(),
            'dwi' => User::where('username', 'dwi_lesmiyati')->firstOrFail(),
            'sujar' => User::where('username', 'sujar_hartono')->firstOrFail(),
            'rya' => User::where('username', 'ryawidyaningtyas')->firstOrFail(),
            'heru' => User::where('username', 'heru_purnomo')->firstOrFail(),
            'widodo' => User::where('username', 'widodo')->firstOrFail(),
            'haris' => User::where('username', 'haris_budiawan')->firstOrFail(),
            'yulita' => User::where('username', 'yulita_winajeng')->firstOrFail(),
            'rusdiyanto' => User::where('username', 'rusdiyanto')->firstOrFail(),
            'diah' => User::where('username', 'diah_utaminingsih')->firstOrFail(),
            'rosyida' => User::where('username', 'rosyida_ramadhani')->firstOrFail(),
            'reny' => User::where('username', 'reny_murni')->firstOrFail(),
            'dinda' => User::where('username', 'dinda_ayu')->firstOrFail(),
        ];

        // Daftar Jadwal Putaran 1
        $schedules = [
            // ==========================================
            // KELAS XII RPL 1 (PUTARAN 1)
            // ==========================================

            // SENIN - XII RPL 1
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['novia']->id,
                'room_id' => $room10->id,
                'subject' => 'PENDIDIKAN AGAMA DAN BUDI PEKERTI',
                'day_of_week' => 'Senin',
                'start_time' => '07:15',
                'end_time' => '08:45',
                'cycle_type' => 'Putaran 1',
            ],
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['dwi']->id,
                'room_id' => $room10->id,
                'subject' => 'MATEMATIKA',
                'day_of_week' => 'Senin',
                'start_time' => '09:45',
                'end_time' => '12:00',
                'cycle_type' => 'Putaran 1',
            ],
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['sujar']->id,
                'room_id' => $room10->id,
                'subject' => 'BAHASA INGGRIS',
                'day_of_week' => 'Senin',
                'start_time' => '12:30',
                'end_time' => '14:00',
                'cycle_type' => 'Putaran 1',
            ],
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['rya']->id,
                'room_id' => $room10->id,
                'subject' => 'BIMBINGAN DAN KONSELING',
                'day_of_week' => 'Senin',
                'start_time' => '14:00',
                'end_time' => '15:30',
                'cycle_type' => 'Putaran 1',
            ],

            // SELASA - XII RPL 1
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['heru']->id,
                'room_id' => $room10->id,
                'subject' => 'BAHASA INDONESIA',
                'day_of_week' => 'Selasa',
                'start_time' => '07:15',
                'end_time' => '09:30',
                'cycle_type' => 'Putaran 1',
            ],
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['widodo']->id,
                'room_id' => $room10->id,
                'subject' => 'PENDIDIKAN PANCASILA',
                'day_of_week' => 'Selasa',
                'start_time' => '09:45',
                'end_time' => '11:15',
                'cycle_type' => 'Putaran 1',
            ],
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['haris']->id,
                'room_id' => $lab16->id,
                'subject' => 'KREATIFITAS, INOVASI DAN KEWIRAUSAHAAN',
                'day_of_week' => 'Selasa',
                'start_time' => '11:15',
                'end_time' => '15:30',
                'cycle_type' => 'Putaran 1',
            ],

            // RABU - XII RPL 1
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['sujar']->id,
                'room_id' => $room10->id,
                'subject' => 'BAHASA INGGRIS',
                'day_of_week' => 'Rabu',
                'start_time' => '07:15',
                'end_time' => '09:30',
                'cycle_type' => 'Putaran 1',
            ],
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['yulita']->id,
                'room_id' => $room10->id,
                'subject' => 'BAHASA JAWA',
                'day_of_week' => 'Rabu',
                'start_time' => '09:45',
                'end_time' => '11:15',
                'cycle_type' => 'Putaran 1',
            ],
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['haris']->id,
                'room_id' => $lab16->id,
                'subject' => 'KREATIFITAS, INOVASI DAN KEWIRAUSAHAAN',
                'day_of_week' => 'Rabu',
                'start_time' => '11:15',
                'end_time' => '15:30',
                'cycle_type' => 'Putaran 1',
            ],

            // KAMIS - XII RPL 1
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['dwi']->id,
                'room_id' => $room10->id,
                'subject' => 'MATEMATIKA',
                'day_of_week' => 'Kamis',
                'start_time' => '07:15',
                'end_time' => '09:30',
                'cycle_type' => 'Putaran 1',
            ],
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['novia']->id,
                'room_id' => $room10->id,
                'subject' => 'PENDIDIKAN AGAMA DAN BUDI PEKERTI',
                'day_of_week' => 'Kamis',
                'start_time' => '09:45',
                'end_time' => '12:00',
                'cycle_type' => 'Putaran 1',
            ],
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['rusdiyanto']->id,
                'room_id' => $lab16->id,
                'subject' => 'MAPEL PILIHAN RPL INTERNET OF THINKS',
                'day_of_week' => 'Kamis',
                'start_time' => '12:30',
                'end_time' => '15:30',
                'cycle_type' => 'Putaran 1',
            ],

            // JUMAT - XII RPL 1
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['heru']->id,
                'room_id' => $room10->id,
                'subject' => 'BAHASA INDONESIA',
                'day_of_week' => 'Jumat',
                'start_time' => '07:15',
                'end_time' => '09:30',
                'cycle_type' => 'Putaran 1',
            ],
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['yulita']->id,
                'room_id' => $room10->id,
                'subject' => 'BAHASA JAWA',
                'day_of_week' => 'Jumat',
                'start_time' => '09:45',
                'end_time' => '11:15',
                'cycle_type' => 'Putaran 1',
            ],
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['sujar']->id,
                'room_id' => $room10->id,
                'subject' => 'BAHASA INGGRIS',
                'day_of_week' => 'Jumat',
                'start_time' => '11:15',
                'end_time' => '14:00',
                'cycle_type' => 'Putaran 1',
            ],


            // ==========================================
            // KELAS XII RPL 2 (PUTARAN 1)
            // ==========================================

            // SENIN - XII RPL 2
            [
                'class_id' => $classRpl2->id,
                'teacher_id' => $teachers['diah']->id,
                'room_id' => $lab19->id,
                'subject' => 'KK 1 RPL BASIS DATA',
                'day_of_week' => 'Senin',
                'start_time' => '07:15',
                'end_time' => '09:30',
                'cycle_type' => 'Putaran 1',
            ],
            [
                'class_id' => $classRpl2->id,
                'teacher_id' => $teachers['rosyida']->id,
                'room_id' => $lab19->id,
                'subject' => 'KK 4 RPL PEMROGRAMAN PERANGKAT BERGERAK',
                'day_of_week' => 'Senin',
                'start_time' => '09:45',
                'end_time' => '15:30',
                'cycle_type' => 'Putaran 1',
            ],

            // SELASA - XII RPL 2
            [
                'class_id' => $classRpl2->id,
                'teacher_id' => $teachers['rosyida']->id,
                'room_id' => $lab19->id,
                'subject' => 'KK 5 RPL ANALISA DAN PERANCANGAN SISTEM',
                'day_of_week' => 'Selasa',
                'start_time' => '07:15',
                'end_time' => '12:00',
                'cycle_type' => 'Putaran 1',
            ],
            [
                'class_id' => $classRpl2->id,
                'teacher_id' => $teachers['reny']->id,
                'room_id' => $lab19->id,
                'subject' => 'KK 2 RPL PEMROGRAMAN GUI',
                'day_of_week' => 'Selasa',
                'start_time' => '12:30',
                'end_time' => '15:30',
                'cycle_type' => 'Putaran 1',
            ],

            // RABU - XII RPL 2
            [
                'class_id' => $classRpl2->id,
                'teacher_id' => $teachers['rusdiyanto']->id,
                'room_id' => $lab19->id,
                'subject' => 'MAPEL PILIHAN RPL INTERNET OF THINKS',
                'day_of_week' => 'Rabu',
                'start_time' => '07:15',
                'end_time' => '10:30',
                'cycle_type' => 'Putaran 1',
            ],
            [
                'class_id' => $classRpl2->id,
                'teacher_id' => $teachers['dinda']->id,
                'room_id' => $lab19->id,
                'subject' => 'KK 3 RPL PEMROGRAMAN WEBSITE',
                'day_of_week' => 'Rabu',
                'start_time' => '10:30',
                'end_time' => '15:30',
                'cycle_type' => 'Putaran 1',
            ],

            // KAMIS - XII RPL 2
            [
                'class_id' => $classRpl2->id,
                'teacher_id' => $teachers['reny']->id,
                'room_id' => $lab19->id,
                'subject' => 'KK 2 RPL PEMROGRAMAN GUI',
                'day_of_week' => 'Kamis',
                'start_time' => '07:15',
                'end_time' => '12:00',
                'cycle_type' => 'Putaran 1',
            ],
            [
                'class_id' => $classRpl2->id,
                'teacher_id' => $teachers['dinda']->id,
                'room_id' => $lab19->id,
                'subject' => 'KK 3 RPL PEMROGRAMAN WEBSITE',
                'day_of_week' => 'Kamis',
                'start_time' => '12:30',
                'end_time' => '15:30',
                'cycle_type' => 'Putaran 1',
            ],

            // JUMAT - XII RPL 2
            [
                'class_id' => $classRpl2->id,
                'teacher_id' => $teachers['rosyida']->id,
                'room_id' => $lab19->id,
                'subject' => 'KK 4 RPL PEMROGRAMAN PERANGKAT BERGERAK',
                'day_of_week' => 'Jumat',
                'start_time' => '07:15',
                'end_time' => '10:30',
                'cycle_type' => 'Putaran 1',
            ],
            [
                'class_id' => $classRpl2->id,
                'teacher_id' => $teachers['dinda']->id,
                'room_id' => $lab19->id,
                'subject' => 'KK 3 RPL PEMROGRAMAN WEBSITE',
                'day_of_week' => 'Jumat',
                'start_time' => '10:30',
                'end_time' => '14:00',
                'cycle_type' => 'Putaran 1',
            ],

            // ==========================================
            // KELAS XII RPL 1 (PUTARAN 2)
            // ==========================================

            // SENIN - XII RPL 1
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['rosyida']->id,
                'room_id' => $lab19->id,
                'subject' => 'KK 4 RPL PEMROGRAMAN PERANGKAT BERGERAK',
                'day_of_week' => 'Senin',
                'start_time' => '07:15',
                'end_time' => '12:00',
                'cycle_type' => 'Putaran 2',
            ],
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['rosyida']->id,
                'room_id' => $lab19->id,
                'subject' => 'KK 5 RPL ANALISA DAN PERANCANGAN SISTEM',
                'day_of_week' => 'Senin',
                'start_time' => '12:30',
                'end_time' => '15:30',
                'cycle_type' => 'Putaran 2',
            ],

            // SELASA - XII RPL 1
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['reny']->id,
                'room_id' => $lab19->id,
                'subject' => 'KK 2 RPL PEMROGRAMAN GUI',
                'day_of_week' => 'Selasa',
                'start_time' => '07:15',
                'end_time' => '12:00',
                'cycle_type' => 'Putaran 2',
            ],
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['diah']->id,
                'room_id' => $lab19->id,
                'subject' => 'KK 1 RPL BASIS DATA',
                'day_of_week' => 'Selasa',
                'start_time' => '12:30',
                'end_time' => '15:30',
                'cycle_type' => 'Putaran 2',
            ],

            // RABU - XII RPL 1
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['reny']->id,
                'room_id' => $lab19->id,
                'subject' => 'KK 2 RPL PEMROGRAMAN GUI',
                'day_of_week' => 'Rabu',
                'start_time' => '07:15',
                'end_time' => '10:30',
                'cycle_type' => 'Putaran 2',
            ],
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['dinda']->id,
                'room_id' => $lab19->id,
                'subject' => 'KK 3 RPL PEMROGRAMAN WEBSITE',
                'day_of_week' => 'Rabu',
                'start_time' => '10:30',
                'end_time' => '15:30',
                'cycle_type' => 'Putaran 2',
            ],

            // KAMIS - XII RPL 1
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['dinda']->id,
                'room_id' => $lab19->id,
                'subject' => 'KK 3 RPL PEMROGRAMAN WEBSITE',
                'day_of_week' => 'Kamis',
                'start_time' => '07:15',
                'end_time' => '10:30',
                'cycle_type' => 'Putaran 2',
            ],
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['rosyida']->id,
                'room_id' => $lab19->id,
                'subject' => 'KK 4 RPL PEMROGRAMAN PERANGKAT BERGERAK',
                'day_of_week' => 'Kamis',
                'start_time' => '10:30',
                'end_time' => '14:00',
                'cycle_type' => 'Putaran 2',
            ],
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['rosyida']->id,
                'room_id' => $lab19->id,
                'subject' => 'KK 5 RPL ANALISA DAN PERANCANGAN SISTEM',
                'day_of_week' => 'Kamis',
                'start_time' => '14:00',
                'end_time' => '15:30',
                'cycle_type' => 'Putaran 2',
            ],

            // JUMAT - XII RPL 1
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['rusdiyanto']->id,
                'room_id' => $lab19->id,
                'subject' => 'MAPEL PILIHAN RPL INTERNET OF THINKS',
                'day_of_week' => 'Jumat',
                'start_time' => '07:15',
                'end_time' => '10:30',
                'cycle_type' => 'Putaran 2',
            ],
            [
                'class_id' => $classRpl1->id,
                'teacher_id' => $teachers['dinda']->id,
                'room_id' => $lab19->id,
                'subject' => 'KK 3 RPL PEMROGRAMAN WEBSITE',
                'day_of_week' => 'Jumat',
                'start_time' => '10:30',
                'end_time' => '14:00',
                'cycle_type' => 'Putaran 2',
            ],

            // ==========================================
            // KELAS XII RPL 2 (PUTARAN 2)
            // ==========================================

            // SENIN - XII RPL 2
            [
                'class_id' => $classRpl2->id,
                'teacher_id' => $teachers['dwi']->id,
                'room_id' => $room10->id,
                'subject' => 'MATEMATIKA',
                'day_of_week' => 'Senin',
                'start_time' => '07:15',
                'end_time' => '09:30',
                'cycle_type' => 'Putaran 2',
            ],
            [
                'class_id' => $classRpl2->id,
                'teacher_id' => $teachers['sujar']->id,
                'room_id' => $room10->id,
                'subject' => 'BAHASA INGGRIS',
                'day_of_week' => 'Senin',
                'start_time' => '09:45',
                'end_time' => '11:15',
                'cycle_type' => 'Putaran 2',
            ],
            [
                'class_id' => $classRpl2->id,
                'teacher_id' => $teachers['heru']->id,
                'room_id' => $room10->id,
                'subject' => 'BAHASA INDONESIA',
                'day_of_week' => 'Senin',
                'start_time' => '11:15',
                'end_time' => '14:00',
                'cycle_type' => 'Putaran 2',
            ],
            [
                'class_id' => $classRpl2->id,
                'teacher_id' => $teachers['yulita']->id,
                'room_id' => $room10->id,
                'subject' => 'BAHASA JAWA',
                'day_of_week' => 'Senin',
                'start_time' => '14:00',
                'end_time' => '15:30',
                'cycle_type' => 'Putaran 2',
            ],

            // SELASA - XII RPL 2
            [
                'class_id' => $classRpl2->id,
                'teacher_id' => $teachers['rusdiyanto']->id,
                'room_id' => $lab16->id,
                'subject' => 'MAPEL PILIHAN RPL INTERNET OF THINKS',
                'day_of_week' => 'Selasa',
                'start_time' => '07:15',
                'end_time' => '10:30',
                'cycle_type' => 'Putaran 2',
            ],
            [
                'class_id' => $classRpl2->id,
                'teacher_id' => $teachers['novia']->id,
                'room_id' => $room10->id,
                'subject' => 'PENDIDIKAN AGAMA DAN BUDI PEKERTI',
                'day_of_week' => 'Selasa',
                'start_time' => '10:30',
                'end_time' => '13:15',
                'cycle_type' => 'Putaran 2',
            ],
            [
                'class_id' => $classRpl2->id,
                'teacher_id' => $teachers['sujar']->id,
                'room_id' => $room10->id,
                'subject' => 'BAHASA INGGRIS',
                'day_of_week' => 'Selasa',
                'start_time' => '13:15',
                'end_time' => '15:30',
                'cycle_type' => 'Putaran 2',
            ],

            // RABU - XII RPL 2
            [
                'class_id' => $classRpl2->id,
                'teacher_id' => $teachers['heru']->id,
                'room_id' => $room10->id,
                'subject' => 'BAHASA INDONESIA',
                'day_of_week' => 'Rabu',
                'start_time' => '07:15',
                'end_time' => '09:30',
                'cycle_type' => 'Putaran 2',
            ],
            [
                'class_id' => $classRpl2->id,
                'teacher_id' => $teachers['rya']->id,
                'room_id' => $room10->id,
                'subject' => 'BIMBINGAN DAN KONSELING',
                'day_of_week' => 'Rabu',
                'start_time' => '09:45',
                'end_time' => '11:15',
                'cycle_type' => 'Putaran 2',
            ],
            [
                'class_id' => $classRpl2->id,
                'teacher_id' => $teachers['dwi']->id,
                'room_id' => $room10->id,
                'subject' => 'MATEMATIKA',
                'day_of_week' => 'Rabu',
                'start_time' => '11:15',
                'end_time' => '14:00',
                'cycle_type' => 'Putaran 2',
            ],
            [
                'class_id' => $classRpl2->id,
                'teacher_id' => $teachers['widodo']->id,
                'room_id' => $room10->id,
                'subject' => 'PENDIDIKAN PANCASILA',
                'day_of_week' => 'Rabu',
                'start_time' => '14:00',
                'end_time' => '15:30',
                'cycle_type' => 'Putaran 2',
            ],

            // KAMIS - XII RPL 2
            [
                'class_id' => $classRpl2->id,
                'teacher_id' => $teachers['sujar']->id,
                'room_id' => $room10->id,
                'subject' => 'BAHASA INGGRIS',
                'day_of_week' => 'Kamis',
                'start_time' => '07:15',
                'end_time' => '09:30',
                'cycle_type' => 'Putaran 2',
            ],
            [
                'class_id' => $classRpl2->id,
                'teacher_id' => $teachers['yulita']->id,
                'room_id' => $room10->id,
                'subject' => 'BAHASA JAWA',
                'day_of_week' => 'Kamis',
                'start_time' => '09:45',
                'end_time' => '11:15',
                'cycle_type' => 'Putaran 2',
            ],
            [
                'class_id' => $classRpl2->id,
                'teacher_id' => $teachers['haris']->id,
                'room_id' => $lab16->id,
                'subject' => 'KREATIFITAS, INOVASI DAN KEWIRAUSAHAAN',
                'day_of_week' => 'Kamis',
                'start_time' => '11:15',
                'end_time' => '15:30',
                'cycle_type' => 'Putaran 2',
            ],

            // JUMAT - XII RPL 2
            [
                'class_id' => $classRpl2->id,
                'teacher_id' => $teachers['novia']->id,
                'room_id' => $room10->id,
                'subject' => 'PENDIDIKAN AGAMA DAN BUDI PEKERTI',
                'day_of_week' => 'Jumat',
                'start_time' => '07:15',
                'end_time' => '09:30',
                'cycle_type' => 'Putaran 2',
            ],
            [
                'class_id' => $classRpl2->id,
                'teacher_id' => $teachers['haris']->id,
                'room_id' => $lab16->id,
                'subject' => 'KREATIFITAS, INOVASI DAN KEWIRAUSAHAAN',
                'day_of_week' => 'Jumat',
                'start_time' => '09:45',
                'end_time' => '14:00',
                'cycle_type' => 'Putaran 2',
            ],
        ];

        foreach ($schedules as $item) {
            Schedule::updateOrCreate(
                [
                    'class_id' => $item['class_id'],
                    'day_of_week' => $item['day_of_week'],
                    'start_time' => $item['start_time'],
                    'cycle_type' => $item['cycle_type'],
                ],
                $item
            );
        }
    }
}
