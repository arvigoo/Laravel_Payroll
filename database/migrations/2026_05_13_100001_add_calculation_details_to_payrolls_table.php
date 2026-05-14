<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            // Tunjangan tambahan
            $table->decimal('tunjangan_insentif', 15, 2)->default(0)->after('tunjangan_bonus');

            // BPJS Ditanggung Karyawan (detail breakdown)
            $table->decimal('jht_employee', 15, 2)->default(0)->after('bpjs_kes_company');
            $table->decimal('jp_employee', 15, 2)->default(0)->after('jht_employee');
            $table->decimal('bpjs_kes_employee', 15, 2)->default(0)->after('jp_employee');
            $table->decimal('bpjs_tambahan', 15, 2)->default(0)->after('bpjs_kes_employee');

            // Total BPJS
            $table->decimal('total_bpjs_company', 15, 2)->default(0)->after('bpjs_tambahan');
            $table->decimal('total_bpjs_employee', 15, 2)->default(0)->after('total_bpjs_company');

            // Gaji Bruto & detail pajak
            $table->decimal('gaji_bruto', 15, 2)->default(0)->after('total_bpjs_employee');
            $table->decimal('biaya_jabatan', 15, 2)->default(0)->after('gaji_bruto');
            $table->decimal('gaji_netto_pajak', 15, 2)->default(0)->after('biaya_jabatan');
            $table->decimal('gaji_setahun', 15, 2)->default(0)->after('gaji_netto_pajak');
            $table->decimal('ptkp', 15, 2)->default(0)->after('gaji_setahun');
            $table->decimal('pkp', 15, 2)->default(0)->after('ptkp');
            $table->decimal('pph21_setahun', 15, 2)->default(0)->after('pkp');

            // Potongan lain
            $table->decimal('total_potongan', 15, 2)->default(0)->after('pph21_setahun');
            $table->decimal('kas_bon', 15, 2)->default(0)->after('total_potongan');

            // Uang makan detail
            $table->integer('hari_makan')->nullable()->after('kas_bon');
            $table->decimal('uang_makan_harian', 15, 2)->default(0)->after('hari_makan');

            // OT rate detail
            $table->decimal('ot_rate_libur', 15, 2)->default(0)->after('uang_makan_harian');

            // Status
            $table->string('status', 20)->default('draft')->after('ot_rate_libur');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'tunjangan_insentif',
                'jht_employee', 'jp_employee', 'bpjs_kes_employee', 'bpjs_tambahan',
                'total_bpjs_company', 'total_bpjs_employee',
                'gaji_bruto', 'biaya_jabatan', 'gaji_netto_pajak',
                'gaji_setahun', 'ptkp', 'pkp', 'pph21_setahun',
                'total_potongan', 'kas_bon',
                'hari_makan', 'uang_makan_harian', 'ot_rate_libur',
                'status',
            ]);
        });
    }
};
