<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    protected $fillable = [
        'employee_id', 'team_id', 'period', 
        // Gaji Pokok & Hari Kerja
        'salary_pokok', 'hari_kerja',
        // Tunjangan
        'allowances', 'uang_jabatan', 'uang_makan', 
        'tunjangan_prestasi', 'tunjangan_bonus', 'tunjangan_insentif',
        // Lembur
        'overtime', 'ot_hari_biasa', 'ot_hari_libur', 
        'ot_per_jam', 'ot_rate_libur',
        'lembur_hari_biasa', 'lembur_hari_libur',
        // Uang Makan detail
        'hari_makan', 'uang_makan_harian',
        // BPJS Perusahaan
        'jkk_jkm', 'jht_company', 'jp_company', 'bpjs_kes_company',
        'total_bpjs_company',
        // BPJS Karyawan
        'jht_employee', 'jp_employee', 'bpjs_kes_employee', 'bpjs_tambahan',
        'bpjs_employee', 'total_bpjs_employee',
        // Gaji Bruto & PPh 21
        'gaji_bruto', 'biaya_jabatan', 'gaji_netto_pajak',
        'gaji_setahun', 'ptkp', 'pkp', 'pph21_setahun', 'pph21',
        // Potongan & Net
        'total_potongan', 'kas_bon', 'net_salary',
        // Status
        'status',
    ];

    protected $casts = [
        'salary_pokok'       => 'decimal:2',
        'allowances'         => 'decimal:2',
        'overtime'           => 'decimal:2',
        'uang_jabatan'       => 'decimal:2',
        'uang_makan'         => 'decimal:2',
        'tunjangan_prestasi' => 'decimal:2',
        'tunjangan_bonus'    => 'decimal:2',
        'tunjangan_insentif' => 'decimal:2',
        'ot_per_jam'         => 'decimal:2',
        'ot_rate_libur'      => 'decimal:2',
        'ot_hari_biasa'      => 'decimal:2',
        'ot_hari_libur'      => 'decimal:2',
        'lembur_hari_biasa'  => 'decimal:2',
        'lembur_hari_libur'  => 'decimal:2',
        'uang_makan_harian'  => 'decimal:2',
        'jkk_jkm'           => 'decimal:2',
        'jht_company'        => 'decimal:2',
        'jp_company'         => 'decimal:2',
        'bpjs_kes_company'   => 'decimal:2',
        'total_bpjs_company' => 'decimal:2',
        'jht_employee'       => 'decimal:2',
        'jp_employee'        => 'decimal:2',
        'bpjs_kes_employee'  => 'decimal:2',
        'bpjs_tambahan'      => 'decimal:2',
        'bpjs_employee'      => 'decimal:2',
        'total_bpjs_employee' => 'decimal:2',
        'gaji_bruto'         => 'decimal:2',
        'biaya_jabatan'      => 'decimal:2',
        'gaji_netto_pajak'   => 'decimal:2',
        'gaji_setahun'       => 'decimal:2',
        'ptkp'               => 'decimal:2',
        'pkp'                => 'decimal:2',
        'pph21_setahun'      => 'decimal:2',
        'pph21'              => 'decimal:2',
        'total_potongan'     => 'decimal:2',
        'kas_bon'            => 'decimal:2',
        'net_salary'         => 'decimal:2',
        'hari_kerja'         => 'integer',
        'hari_makan'         => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Take Home Pay accessor
     */
    public function getTakeHomePayAttribute(): float
    {
        return floatval($this->net_salary);
    }
}