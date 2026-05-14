<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('payrolls', function (Blueprint $table) {
            // Kolom Kehadiran & Tunjangan
            $table->integer('hari_kerja')->nullable();
            $table->decimal('uang_jabatan', 15, 2)->default(0);
            $table->decimal('uang_makan', 15, 2)->default(0);
            $table->decimal('tunjangan_prestasi', 15, 2)->default(0);
            $table->decimal('tunjangan_bonus', 15, 2)->default(0); // THR / Insentif

            // Kolom Detail Lembur (OT)
            $table->decimal('ot_hari_biasa', 8, 2)->default(0); // Jam OT Hari Biasa
            $table->decimal('ot_hari_libur', 8, 2)->default(0); // Jam OT Hari Libur
            $table->decimal('ot_per_jam', 15, 2)->default(0);   // Rate OT per Jam
            $table->decimal('lembur_hari_biasa', 15, 2)->default(0); // Total Nominal Lembur Biasa
            $table->decimal('lembur_hari_libur', 15, 2)->default(0); // Total Nominal Lembur Libur
        });
    }

    public function down()
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'hari_kerja', 'uang_jabatan', 'uang_makan', 'tunjangan_prestasi', 'tunjangan_bonus',
                'ot_hari_biasa', 'ot_hari_libur', 'ot_per_jam', 'lembur_hari_biasa', 'lembur_hari_libur'
            ]);
        });
    }
};