<?php

namespace App\Services;

/**
 * PayrollCalculationService
 * 
 * Engine kalkulasi gaji untuk PT INDOBOX UTAMA JAYA.
 * Menangani 5 kategori: Direksi, Staff, General, Produksi 1, Produksi 2
 * Sesuai dengan format Excel Gaji November 2025.
 */
class PayrollCalculationService
{
    /**
     * BPJS rates per kategori
     * Direksi memiliki rate JHT & BPJS Kes perusahaan yang berbeda
     */
    private const BPJS_RATES = [
        'Direksi' => [
            'jkk_jkm'       => 0.0119,
            'jht_company'    => 0.057,   // 5.7% (lebih tinggi dari non-direksi)
            'jp_company'     => 0.02,
            'bpjs_kes_company' => 0.05,  // 5% (lebih tinggi dari non-direksi)
            // Direksi tidak ada potongan BPJS karyawan
            'jht_employee'   => 0,
            'jp_employee'    => 0,
            'bpjs_kes_employee' => 0,
            'bpjs_tambahan'  => 0,
        ],
        'default' => [
            'jkk_jkm'       => 0.0119,
            'jht_company'    => 0.02,
            'jp_company'     => 0.02,
            'bpjs_kes_company' => 0.04,
            'jht_employee'   => 0.02,
            'jp_employee'    => 0.01,
            'bpjs_kes_employee' => 0.01,
            'bpjs_tambahan'  => 0.01,
        ],
    ];

    /**
     * PTKP (Penghasilan Tidak Kena Pajak) per status
     */
    private const PTKP = [
        'TK' => 54000000,
        'K'  => 58500000,
        'K1' => 63000000,
        'K2' => 67500000,
        'K3' => 72000000,
    ];

    /**
     * Kalkulasi lengkap payroll untuk satu karyawan
     * 
     * @param string $category Kategori payroll (Direksi/Staff/General/Produksi 1/Produksi 2)
     * @param array $inputs Data input dari form/database
     * @return array Semua komponen gaji yang sudah dihitung
     */
    public function calculate(string $category, array $inputs): array
    {
        // === 1. GAJI POKOK ===
        $gajiPokok = $this->calculateGajiPokok($category, $inputs);

        // === 2. TUNJANGAN ===
        $tunjanganJabatan  = floatval($inputs['uang_jabatan'] ?? 0);
        $tunjanganPrestasi = floatval($inputs['tunjangan_prestasi'] ?? 0);
        $tunjanganInsentif = floatval($inputs['tunjangan_insentif'] ?? 0);
        $tunjanganBonus    = floatval($inputs['tunjangan_bonus'] ?? 0);

        // === 3. LEMBUR ===
        $lembur = $this->calculateLembur($category, $inputs);

        // === 4. UANG MAKAN ===
        $uangMakan = $this->calculateUangMakan($category, $inputs);

        // === 5. TOTAL GAJI (sebelum BPJS) ===
        $totalGaji = $gajiPokok + $tunjanganJabatan + $tunjanganPrestasi 
                   + $tunjanganInsentif + $tunjanganBonus 
                   + $lembur['total'] + $uangMakan;

        // === 6. BPJS ===
        $bpjs = $this->calculateBpjs($category, $gajiPokok, $inputs);

        // === 7. GAJI BRUTO (Total Gaji + BPJS Perusahaan) ===
        $gajiBruto = $totalGaji + $bpjs['total_company'];

        // === 8. PPh 21 ===
        $taxStatus = $inputs['tax_status'] ?? 'TK';
        
        if ($category === 'Direksi') {
            // Direksi: PPh 21 manual input atau fixed
            $pph21Data = [
                'biaya_jabatan' => 0,
                'gaji_netto_pajak' => 0,
                'gaji_setahun' => 0,
                'ptkp' => self::PTKP[$taxStatus] ?? 54000000,
                'pkp' => 0,
                'pph21_setahun' => floatval($inputs['pph21'] ?? 0) * 12,
                'pph21_sebulan' => floatval($inputs['pph21'] ?? 0),
            ];
        } else {
            $pph21Data = $this->calculatePph21($totalGaji, $bpjs, $taxStatus);
        }

        // === 9. TOTAL POTONGAN ===
        $totalPotongan = $bpjs['total_employee'] + $pph21Data['pph21_sebulan'];
        $kasBon = floatval($inputs['kas_bon'] ?? 0);

        // === 10. NET SALARY (Take Home Pay) ===
        $netSalary = $totalGaji - $totalPotongan - $kasBon;

        return [
            // Gaji Pokok
            'salary_pokok'       => $gajiPokok,
            'hari_kerja'         => intval($inputs['hari_kerja'] ?? 0),

            // Tunjangan
            'uang_jabatan'       => $tunjanganJabatan,
            'tunjangan_prestasi' => $tunjanganPrestasi,
            'tunjangan_insentif' => $tunjanganInsentif,
            'tunjangan_bonus'    => $tunjanganBonus,

            // Lembur
            'ot_per_jam'         => $lembur['ot_per_jam'],
            'ot_hari_biasa'      => floatval($inputs['ot_hari_biasa'] ?? 0),
            'ot_hari_libur'      => floatval($inputs['ot_hari_libur'] ?? 0),
            'ot_rate_libur'      => $lembur['ot_rate_libur'],
            'lembur_hari_biasa'  => $lembur['lembur_hari_biasa'],
            'lembur_hari_libur'  => $lembur['lembur_hari_libur'],
            'overtime'           => $lembur['total'],

            // Uang Makan
            'uang_makan'         => $uangMakan,
            'hari_makan'         => intval($inputs['hari_makan'] ?? $inputs['hari_kerja'] ?? 0),
            'uang_makan_harian'  => floatval($inputs['uang_makan_harian'] ?? 0),

            // Allowances total (untuk backward compat)
            'allowances'         => $tunjanganJabatan + $tunjanganPrestasi + $tunjanganInsentif + $tunjanganBonus,

            // BPJS Perusahaan
            'jkk_jkm'           => $bpjs['jkk_jkm'],
            'jht_company'       => $bpjs['jht_company'],
            'jp_company'        => $bpjs['jp_company'],
            'bpjs_kes_company'  => $bpjs['bpjs_kes_company'],
            'total_bpjs_company' => $bpjs['total_company'],

            // BPJS Karyawan
            'jht_employee'      => $bpjs['jht_employee'],
            'jp_employee'       => $bpjs['jp_employee'],
            'bpjs_kes_employee' => $bpjs['bpjs_kes_employee'],
            'bpjs_tambahan'     => $bpjs['bpjs_tambahan'],
            'bpjs_employee'     => $bpjs['total_employee'],
            'total_bpjs_employee' => $bpjs['total_employee'],

            // Gaji Bruto
            'gaji_bruto'        => $gajiBruto,

            // PPh 21 Detail
            'biaya_jabatan'     => $pph21Data['biaya_jabatan'],
            'gaji_netto_pajak'  => $pph21Data['gaji_netto_pajak'],
            'gaji_setahun'      => $pph21Data['gaji_setahun'],
            'ptkp'              => $pph21Data['ptkp'],
            'pkp'               => $pph21Data['pkp'],
            'pph21_setahun'     => $pph21Data['pph21_setahun'],
            'pph21'             => $pph21Data['pph21_sebulan'],

            // Potongan
            'total_potongan'    => $totalPotongan,
            'kas_bon'           => $kasBon,

            // Net Salary
            'net_salary'        => $netSalary,
        ];
    }

    /**
     * Hitung Gaji Pokok berdasarkan kategori
     */
    private function calculateGajiPokok(string $category, array $inputs): float
    {
        if (in_array($category, ['Produksi 1', 'Produksi 2'])) {
            // Produksi: gaji harian × hari kerja
            $dailyRate = floatval($inputs['daily_rate'] ?? 0);
            $hariKerja = intval($inputs['hari_kerja'] ?? 30);
            
            if ($dailyRate > 0) {
                return $dailyRate * $hariKerja;
            }
        }

        // Direksi, Staff, General: gaji pokok fixed
        return floatval($inputs['base_salary'] ?? $inputs['salary_pokok'] ?? 0);
    }

    /**
     * Hitung Lembur berdasarkan kategori
     */
    private function calculateLembur(string $category, array $inputs): array
    {
        $result = [
            'ot_per_jam'       => 0,
            'ot_rate_libur'    => 0,
            'lembur_hari_biasa' => 0,
            'lembur_hari_libur' => 0,
            'total'            => 0,
        ];

        if ($category === 'Direksi' || $category === 'Staff') {
            // Direksi & Staff tidak ada lembur
            return $result;
        }

        $gajiPokok = floatval($inputs['base_salary'] ?? $inputs['salary_pokok'] ?? 0);
        
        // OT rate = gaji pokok / 173 (standar jam kerja sebulan)
        $otPerJam = $gajiPokok > 0 ? $gajiPokok / 173 : 0;
        $result['ot_per_jam'] = $otPerJam;

        if ($category === 'Produksi 2') {
            // Produksi 2: dua tipe lembur (hari libur dan hari biasa)
            $jamLibur = floatval($inputs['ot_hari_libur'] ?? 0);
            $jamBiasa = floatval($inputs['ot_hari_biasa'] ?? 0);
            
            $result['ot_rate_libur'] = $otPerJam; // bisa beda rate kalau perlu
            $result['lembur_hari_libur'] = $jamLibur * $otPerJam;
            $result['lembur_hari_biasa'] = $jamBiasa * $otPerJam;
            $result['total'] = $result['lembur_hari_libur'] + $result['lembur_hari_biasa'];
        } else {
            // General & Produksi 1: satu tipe lembur
            $jamOT = floatval($inputs['ot_hari_biasa'] ?? $inputs['overtime_hours'] ?? 0);
            $result['lembur_hari_biasa'] = $jamOT * $otPerJam;
            $result['total'] = $result['lembur_hari_biasa'];
        }

        return $result;
    }

    /**
     * Hitung Uang Makan berdasarkan kategori
     */
    private function calculateUangMakan(string $category, array $inputs): float
    {
        if ($category === 'Direksi' || $category === 'Staff') {
            return 0;
        }

        $hariMakan = intval($inputs['hari_makan'] ?? $inputs['hari_kerja'] ?? 0);
        $uangMakanHarian = floatval($inputs['uang_makan_harian'] ?? 0);

        if ($uangMakanHarian > 0 && $hariMakan > 0) {
            return $hariMakan * $uangMakanHarian;
        }

        // Fallback: gunakan nilai langsung dari input
        return floatval($inputs['uang_makan'] ?? 0);
    }

    /**
     * Hitung BPJS Perusahaan & Karyawan
     * 
     * Base BPJS dihitung dari gaji pokok (bukan total gaji)
     * sesuai dengan rumus di Excel
     */
    private function calculateBpjs(string $category, float $gajiPokok, array $inputs): array
    {
        $rates = ($category === 'Direksi') 
            ? self::BPJS_RATES['Direksi'] 
            : self::BPJS_RATES['default'];

        // Untuk Direksi, BPJS bisa fixed (dari Excel: JKK/JKM=595K, JHT=2.85M, JP=1M, KES=600K)
        if ($category === 'Direksi') {
            return [
                'jkk_jkm'       => floatval($inputs['jkk_jkm'] ?? 595000),
                'jht_company'   => floatval($inputs['jht_company'] ?? 2850000),
                'jp_company'    => floatval($inputs['jp_company'] ?? 1000000),
                'bpjs_kes_company' => floatval($inputs['bpjs_kes_company'] ?? 600000),
                'total_company' => floatval($inputs['jkk_jkm'] ?? 595000) 
                                 + floatval($inputs['jht_company'] ?? 2850000)
                                 + floatval($inputs['jp_company'] ?? 1000000) 
                                 + floatval($inputs['bpjs_kes_company'] ?? 600000),
                'jht_employee'  => 0,
                'jp_employee'   => 0,
                'bpjs_kes_employee' => 0,
                'bpjs_tambahan' => 0,
                'total_employee' => 0,
            ];
        }

        // Non-Direksi: hitung berdasarkan persentase dari gaji pokok
        // Base BPJS menggunakan UMK/gaji pokok yang terdaftar di BPJS
        // Dari Excel: base = 4877211 (UMK Karawang) untuk kebanyakan karyawan
        $baseBpjs = floatval($inputs['base_salary'] ?? $gajiPokok);

        $jkkJkm      = $baseBpjs * $rates['jkk_jkm'];
        $jhtCompany   = $baseBpjs * $rates['jht_company'];
        $jpCompany    = $baseBpjs * $rates['jp_company'];
        $bpjsKesComp  = $baseBpjs * $rates['bpjs_kes_company'];
        $totalCompany = $jkkJkm + $jhtCompany + $jpCompany + $bpjsKesComp;

        $jhtEmployee   = $baseBpjs * $rates['jht_employee'];
        $jpEmployee    = $baseBpjs * $rates['jp_employee'];
        $bpjsKesEmp    = $baseBpjs * $rates['bpjs_kes_employee'];
        
        // BPJS tambahan: hanya jika tax_status memiliki tanggungan
        $taxStatus = $inputs['tax_status'] ?? 'TK';
        $bpjsTambahan = 0;
        if (in_array($taxStatus, ['K', 'K1', 'K2', 'K3'])) {
            // Dari Excel: tambahan = 1% dari base salary (untuk yang punya tanggungan BPJS Kes)
            // Ini terlihat sebagai kolom "TAMBAHAN" di Excel
            $bpjsTambahan = $baseBpjs * $rates['bpjs_tambahan'];
        }
        
        $totalEmployee = $jhtEmployee + $jpEmployee + $bpjsKesEmp + $bpjsTambahan;

        return [
            'jkk_jkm'       => $jkkJkm,
            'jht_company'   => $jhtCompany,
            'jp_company'    => $jpCompany,
            'bpjs_kes_company' => $bpjsKesComp,
            'total_company' => $totalCompany,
            'jht_employee'  => $jhtEmployee,
            'jp_employee'   => $jpEmployee,
            'bpjs_kes_employee' => $bpjsKesEmp,
            'bpjs_tambahan' => $bpjsTambahan,
            'total_employee' => $totalEmployee,
        ];
    }

    /**
     * Hitung PPh 21 dengan Netto Method (annualized)
     */
    private function calculatePph21(float $totalGaji, array $bpjs, string $taxStatus): array
    {
        // Step 1: Gaji Bruto untuk pajak = Total Gaji + BPJS TK karyawan + JKN karyawan
        $bpjsTk = ($bpjs['jht_employee'] ?? 0) + ($bpjs['jp_employee'] ?? 0);
        $jkn = $bpjs['bpjs_kes_employee'] ?? 0;
        $gajiBrutoPajak = $totalGaji + $bpjsTk + $jkn;

        // Step 2: Biaya Jabatan = 5% x Gaji Bruto (max Rp 500.000/bulan)
        $biayaJabatan = min($gajiBrutoPajak * 0.05, 500000);

        // Step 3: Gaji Netto = Gaji Bruto Pajak - Biaya Jabatan
        $gajiNetto = $gajiBrutoPajak - $biayaJabatan;

        // Step 4: Gaji Setahun = Gaji Netto × 12
        $gajiSetahun = $gajiNetto * 12;

        // Step 5: PTKP
        $ptkp = self::PTKP[$taxStatus] ?? 54000000;

        // Step 6: PKP = Gaji Setahun - PTKP (min 0)
        $pkp = max(0, $gajiSetahun - $ptkp);
        // Bulatkan ke bawah per ribuan
        $pkp = floor($pkp / 1000) * 1000;

        // Step 7: PPh 21 Setahun (tarif progresif)
        $pph21Setahun = $this->calculateProgressiveTax($pkp);

        // Step 8: PPh 21 Sebulan
        $pph21Sebulan = $pph21Setahun / 12;
        // Bulatkan
        $pph21Sebulan = round($pph21Sebulan, 0);

        return [
            'biaya_jabatan'   => $biayaJabatan,
            'gaji_netto_pajak' => $gajiNetto,
            'gaji_setahun'    => $gajiSetahun,
            'ptkp'            => $ptkp,
            'pkp'             => $pkp,
            'pph21_setahun'   => $pph21Setahun,
            'pph21_sebulan'   => $pph21Sebulan,
        ];
    }

    /**
     * Hitung pajak progresif berdasarkan PKP
     * 
     * Tarif PPh 21:
     * - 5%  : 0 - 60 juta
     * - 15% : 60 juta - 250 juta
     * - 25% : 250 juta - 500 juta
     * - 30% : 500 juta - 5 miliar
     * - 35% : > 5 miliar
     */
    private function calculateProgressiveTax(float $pkp): float
    {
        if ($pkp <= 0) return 0;

        $tax = 0;
        $brackets = [
            [60000000,    0.05],
            [190000000,   0.15],  // 250M - 60M = 190M
            [250000000,   0.25],  // 500M - 250M = 250M
            [4500000000,  0.30],  // 5B - 500M = 4.5B
            [PHP_FLOAT_MAX, 0.35],
        ];

        $remaining = $pkp;
        foreach ($brackets as [$limit, $rate]) {
            if ($remaining <= 0) break;
            $taxable = min($remaining, $limit);
            $tax += $taxable * $rate;
            $remaining -= $taxable;
        }

        return $tax;
    }

    /**
     * Get PTKP value by tax status
     */
    public function getPtkp(string $taxStatus): float
    {
        return self::PTKP[$taxStatus] ?? 54000000;
    }

    /**
     * Get all PTKP values
     */
    public function getAllPtkp(): array
    {
        return self::PTKP;
    }

    /**
     * Get BPJS rates for a category
     */
    public function getBpjsRates(string $category): array
    {
        return ($category === 'Direksi') 
            ? self::BPJS_RATES['Direksi'] 
            : self::BPJS_RATES['default'];
    }
}
