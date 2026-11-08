<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to restore 19 students and 19 parents mistakenly deleted by dummy purge wildcard.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        try {
            $restoreSql = "INSERT INTO `users` (`id`,`role_id`,`parent_id`,`class_id`,`student_status`,`graduation_year`,`last_class_name`,`username`,`password`,`fullname`,`nip_nisn`,`email`,`phone`,`remember_token`,`created_at`,`updated_at`) VALUES \n" .
            "(12400,3,12401,13,'aktif',NULL,NULL,'rizky_ahmad','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','RIZKY AHMAD GHOFUR RAMDANI','19406',NULL,NULL,NULL,'2026-11-05 05:53:44','2026-11-05 05:53:44'),
            (12401,4,12400,13,'aktif',NULL,NULL,'P19406','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','Orang Tua RIZKY AHMAD GHOFUR RAMDANI',NULL,NULL,NULL,NULL,'2026-11-05 05:53:44','2026-11-05 05:53:44'),
            (12570,3,12571,10,'aktif',NULL,NULL,'bayu_ahmad','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','BAYU AHMAD SUSENA','19096',NULL,NULL,NULL,'2026-11-05 05:53:46','2026-11-05 05:53:46'),
            (12571,4,12570,10,'aktif',NULL,NULL,'P19096','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','Orang Tua BAYU AHMAD SUSENA',NULL,NULL,NULL,NULL,'2026-11-05 05:53:46','2026-11-05 05:53:46'),
            (12900,3,12901,30,'aktif',NULL,NULL,'risti_rahmadhani','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','RISTI RAHMADHANI','19369',NULL,NULL,NULL,'2026-11-05 05:53:48','2026-11-05 05:53:48'),
            (12901,4,12900,30,'aktif',NULL,NULL,'P19369','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','Orang Tua RISTI RAHMADHANI',NULL,NULL,NULL,NULL,'2026-11-05 05:53:48','2026-11-05 05:53:48'),
            (13134,3,13135,6,'aktif',NULL,NULL,'ahmad_jannu','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','AHMAD JANNU SHAGUFTA','19019',NULL,NULL,NULL,'2026-11-05 05:53:50','2026-11-05 05:53:50'),
            (13135,4,13134,6,'aktif',NULL,NULL,'P19019','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','Orang Tua AHMAD JANNU SHAGUFTA',NULL,NULL,NULL,NULL,'2026-11-05 05:53:50','2026-11-05 05:53:50'),
            (13340,3,13341,58,'aktif',NULL,NULL,'shila_rahmadani','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','SHILA RAHMADANI','18617',NULL,NULL,NULL,'2026-11-05 05:53:52','2026-11-05 05:53:52'),
            (13341,4,13340,58,'aktif',NULL,NULL,'P18617','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','Orang Tua SHILA RAHMADANI',NULL,NULL,NULL,NULL,'2026-11-05 05:53:52','2026-11-05 05:53:52'),
            (13726,3,13727,42,'aktif',NULL,NULL,'calvin_ahmad','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','CALVIN AHMAD PRASETYA','18522',NULL,NULL,NULL,'2026-11-05 05:53:55','2026-11-05 05:53:55'),
            (13727,4,13726,42,'aktif',NULL,NULL,'P18522','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','Orang Tua CALVIN AHMAD PRASETYA',NULL,NULL,NULL,NULL,'2026-11-05 05:53:55','2026-11-05 05:53:55'),
            (13778,3,13779,53,'aktif',NULL,NULL,'agustin_rahmadani','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','AGUSTIN RAHMADANI CINTA ADZANA','18656',NULL,NULL,NULL,'2026-11-05 05:53:55','2026-11-05 05:53:55'),
            (13779,4,13778,53,'aktif',NULL,NULL,'P18656','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','Orang Tua AGUSTIN RAHMADANI CINTA ADZANA',NULL,NULL,NULL,NULL,'2026-11-05 05:53:55','2026-11-05 05:53:55'),
            (14138,3,14139,34,'aktif',NULL,NULL,'ahmad_fiil','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','AHMAD FI`IL MUZAKI','18369',NULL,NULL,NULL,'2026-11-05 05:53:58','2026-11-05 05:53:59'),
            (14139,4,14138,34,'aktif',NULL,NULL,'P18369','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','Orang Tua AHMAD FI`IL MUZAKI',NULL,NULL,NULL,NULL,'2026-11-05 05:53:59','2026-11-05 05:53:59'),
            (14140,3,14141,34,'aktif',NULL,NULL,'ahmad_muhaimin','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','AHMAD MUHAIMIN EFENDI','18370',NULL,NULL,NULL,'2026-11-05 05:53:59','2026-11-05 05:53:59'),
            (14141,4,14140,34,'aktif',NULL,NULL,'P18370','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','Orang Tua AHMAD MUHAIMIN EFENDI',NULL,NULL,NULL,NULL,'2026-11-05 05:53:59','2026-11-05 05:53:59'),
            (14166,3,14167,34,'aktif',NULL,NULL,'dimas_ahmad','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','DIMAS AHMAD DARMAWAN','18383',NULL,NULL,NULL,'2026-11-05 05:53:59','2026-11-05 05:53:59'),
            (14167,4,14166,34,'aktif',NULL,NULL,'P18383','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','Orang Tua DIMAS AHMAD DARMAWAN',NULL,NULL,NULL,NULL,'2026-11-05 05:53:59','2026-11-05 05:53:59'),
            (14208,3,14209,37,'aktif',NULL,NULL,'ahmad_fathi','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','AHMAD FATHI AL-GHAZALI','18404',NULL,NULL,NULL,'2026-11-05 05:53:59','2026-11-05 05:53:59'),
            (14209,4,14208,37,'aktif',NULL,NULL,'P18404','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','Orang Tua AHMAD FATHI AL-GHAZALI',NULL,NULL,NULL,NULL,'2026-11-05 05:53:59','2026-11-05 05:53:59'),
            (14280,3,14281,38,'aktif',NULL,NULL,'ahmad_yusuf','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','AHMAD YUSUF','18441',NULL,NULL,NULL,'2026-11-05 05:54:00','2026-11-05 05:54:00'),
            (14281,4,14280,38,'aktif',NULL,NULL,'P18441','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','Orang Tua AHMAD YUSUF',NULL,NULL,NULL,NULL,'2026-11-05 05:54:00','2026-11-05 05:54:00'),
            (14292,3,14293,38,'aktif',NULL,NULL,'diaz_rahmad','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','DIAZ RAHMAD FARUKHI','18447',NULL,NULL,NULL,'2026-11-05 05:54:00','2026-11-05 05:54:00'),
            (14293,4,14292,38,'aktif',NULL,NULL,'P18447','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','Orang Tua DIAZ RAHMAD FARUKHI',NULL,NULL,NULL,NULL,'2026-11-05 05:54:00','2026-11-05 05:54:00'),
            (14330,3,14331,38,'aktif',NULL,NULL,'rahmad_putra','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','RAHMAD PUTRA SETIAWAN','18466',NULL,NULL,NULL,'2026-11-05 05:54:00','2026-11-05 05:54:00'),
            (14331,4,14330,38,'aktif',NULL,NULL,'P18466','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','Orang Tua RAHMAD PUTRA SETIAWAN',NULL,NULL,NULL,NULL,'2026-11-05 05:54:00','2026-11-05 05:54:00'),
            (14550,3,14551,91,'aktif',NULL,NULL,'revalia_rahmadani','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','REVALIA RAHMADANI','18071',NULL,NULL,NULL,'2026-11-05 05:54:02','2026-11-05 05:54:02'),
            (14551,4,14550,91,'aktif',NULL,NULL,'P18071','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','Orang Tua REVALIA RAHMADANI',NULL,NULL,NULL,NULL,'2026-11-05 05:54:02','2026-11-05 05:54:02'),
            (14874,3,14875,73,'aktif',NULL,NULL,'fahri_ahmad','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','FAHRI AHMAD FAUZAN','17915',NULL,NULL,NULL,'2026-11-05 05:54:05','2026-11-05 05:54:05'),
            (14875,4,14874,73,'aktif',NULL,NULL,'P17915','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','Orang Tua FAHRI AHMAD FAUZAN',NULL,NULL,NULL,NULL,'2026-11-05 05:54:05','2026-11-05 05:54:05'),
            (14928,3,14929,74,'aktif',NULL,NULL,'armita_rahmadhanti','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','ARMITA RAHMADHANTI','17942',NULL,NULL,NULL,'2026-11-05 05:54:05','2026-11-05 05:54:05'),
            (14929,4,14928,74,'aktif',NULL,NULL,'P17942','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','Orang Tua ARMITA RAHMADHANTI',NULL,NULL,NULL,NULL,'2026-11-05 05:54:05','2026-11-05 05:54:05'),
            (15218,3,15219,65,'aktif',NULL,NULL,'fahriza_ahmad','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','FAHRIZA AHMAD','17765',NULL,NULL,NULL,'2026-11-05 05:54:08','2026-11-05 05:54:08'),
            (15219,4,15218,65,'aktif',NULL,NULL,'P17765','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','Orang Tua FAHRIZA AHMAD',NULL,NULL,NULL,NULL,'2026-11-05 05:54:08','2026-11-05 05:54:08'),
            (15270,3,15271,66,'aktif',NULL,NULL,'ahmad_priandoko','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','AHMAD PRIANDOKO','17793',NULL,NULL,NULL,'2026-11-05 05:54:08','2026-11-05 05:54:08'),
            (15271,4,15270,66,'aktif',NULL,NULL,'P17793','$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy','Orang Tua AHMAD PRIANDOKO',NULL,NULL,NULL,NULL,'2026-11-05 05:54:08','2026-11-05 05:54:08')" .
            "\n ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`), `class_id`=VALUES(`class_id`), `student_status`=VALUES(`student_status`), `username`=VALUES(`username`), `fullname`=VALUES(`fullname`), `nip_nisn`=VALUES(`nip_nisn`);";

            DB::unprepared($restoreSql);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
