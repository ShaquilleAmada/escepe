<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_otps', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();          // email target (waka)
            $table->string('otp_hash');                // Hash::make(otp), tidak pernah simpan plaintext
            $table->unsignedTinyInteger('attempts')->default(0); // jumlah percobaan salah
            $table->timestamp('expires_at');           // kadaluarsa 10 menit
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_otps');
    }
};
