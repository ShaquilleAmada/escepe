<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cycle_histories', function (Blueprint $table) {
            $table->id();
            $table->string('cycle_name', 20); // Putaran 1 / Putaran 2
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('class_mapping_summary')->nullable(); // JSON / string ringkasan status kelas
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cycle_histories');
    }
};
