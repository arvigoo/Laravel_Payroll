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
        Schema::table('payrolls', function (Blueprint $table) {
            // Kolom untuk menampung input manual BPJS Perusahaan
            $table->decimal('jkk_jkm', 15, 2)->default(0)->after('allowances');
            $table->decimal('jht_company', 15, 2)->default(0)->after('jkk_jkm');
            $table->decimal('jp_company', 15, 2)->default(0)->after('jht_company');
            $table->decimal('bpjs_kes_company', 15, 2)->default(0)->after('jp_company');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['jkk_jkm', 'jht_company', 'jp_company', 'bpjs_kes_company']);
        });
    }
};
