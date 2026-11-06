<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Presensi - {{ $class->class_name }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
            margin: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .header h2 {
            margin: 0;
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header h3 {
            margin: 4px 0 0 0;
            font-size: 13px;
            font-weight: normal;
        }
        .header p {
            margin: 3px 0 0 0;
            font-size: 10px;
            color: #64748b;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 14px;
        }
        .meta-table td {
            padding: 2px 0;
            font-size: 11px;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table-data th, .table-data td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            text-align: left;
        }
        .table-data th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-align: center;
        }
        .text-center {
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 9px;
            font-weight: bold;
            border-radius: 4px;
        }
        .footer-sign {
            width: 100%;
            margin-top: 30px;
        }
        .footer-sign td {
            width: 50%;
            vertical-align: top;
            text-align: center;
        }
        .sign-space {
            height: 55px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>PEMERINTAH DAERAH DAERAH ISTIMEWA YOGYAKARTA</h2>
        <h2>DINAS PENDIDIKAN, PEMUDA DAN OLAHRAGA</h2>
        <h3>SMK NEGERI 1 BANTUL</h3>
        <p>Jl. Parangtritis Km 11, Sabdodadi, Bantul, Yogyakarta | Telepon: (0274) 367156</p>
    </div>

    <div style="text-align: center; margin-bottom: 15px;">
        <strong style="font-size: 12px; text-decoration: underline;">LAPORAN REKAPITULASI PRESENSI KELAS</strong>
    </div>

    <table class="meta-table">
        <tr>
            <td style="width: 18%;"><strong>Kelas</strong></td>
            <td style="width: 2%;">:</td>
            <td style="width: 40%;">{{ $class->class_name }}</td>
            <td style="width: 20%;"><strong>Periode</strong></td>
            <td style="width: 2%;">:</td>
            <td style="width: 18%;">{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td><strong>Wali Kelas</strong></td>
            <td>:</td>
            <td>{{ $walasName }}</td>
            <td><strong>Hari Efektif KBM</strong></td>
            <td>:</td>
            <td>{{ $effectiveDays }} Hari</td>
        </tr>
    </table>

    <table class="table-data">
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th style="width: 55px;">NIS</th>
                <th>Nama Siswa</th>
                <th style="width: 35px;">H</th>
                <th style="width: 35px;">I</th>
                <th style="width: 35px;">S</th>
                <th style="width: 35px;">A</th>
                <th style="width: 45px;">Sesi</th>
                <th style="width: 140px;">% Hadir (Cakupan)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $idx => $s)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-center">{{ $s['nip_nisn'] }}</td>
                    <td>{{ $s['fullname'] }}</td>
                    <td class="text-center">{{ $s['hadir'] }}</td>
                    <td class="text-center">{{ $s['izin'] }}</td>
                    <td class="text-center">{{ $s['sakit'] }}</td>
                    <td class="text-center">{{ $s['alpa'] }}</td>
                    <td class="text-center">{{ $s['total_pertemuan'] }}</td>
                    <td class="text-center">
                        <strong>{{ $s['persentase_kehadiran'] }}</strong>
                        <span style="font-size: 9px; color: #64748b;">({{ $s['cakupan'] }})</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data siswa.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="footer-sign">
        <tr>
            <td>
                <br>
                Guru BK / Petugas Rekap,
                <div class="sign-space"></div>
                <strong>{{ $printedByName }}</strong><br>
                <span>NIP / ID: {{ $printedById }}</span>
            </td>
            <td>
                Bantul, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
                Wali Kelas {{ $class->class_name }},
                <div class="sign-space"></div>
                <strong>{{ $walasName }}</strong><br>
                <span>NIP. {{ $walasNip }}</span>
            </td>
        </tr>
    </table>

    <div style="margin-top: 15px; font-size: 8px; color: #94a3b8; text-align: right;">
        Dicetak otomatis oleh Sistem SCP pada {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }} oleh {{ $printedByName }} (ID: {{ $printedById }}).
    </div>
</body>
</html>

