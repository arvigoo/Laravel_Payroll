<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->time('check_in_start')->default('07:00:00');  // batas paling awal absen masuk
            $table->time('check_in_end')->default('09:00:00');    // batas akhir tidak terlambat
            $table->time('check_out_start')->default('16:00:00'); // jam pulang paling awal
            $table->time('work_end')->default('17:00:00');        // jam kerja standar berakhir (mulai OT)
            $table->integer('ot_multiplier')->default(2);         // kelipatan lembur (x2 = 2/173 gaji pokok/jam)
            $table->timestamps();

            $table->unique('team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_settings');
    }
};
