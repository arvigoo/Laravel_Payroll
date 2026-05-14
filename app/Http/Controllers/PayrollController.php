<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Payroll;
use App\Services\PayrollCalculationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class PayrollController extends Controller
{
    protected PayrollCalculationService $calculator;

    public function __construct(PayrollCalculationService $calculator)
    {
        $this->calculator = $calculator;
    }

    /**
     * Tampilkan Daftar Payroll per Bulan dengan filter kategori
     */
    public function index(Request $request)
    {
        $period = $request->query('period', date('Y-m'));

        $payrolls = Payroll::with('employee')
            ->where('team_id', $request->user()->current_team_id)
            ->where('period', $period)
            ->get();

        return Inertia::render('Payroll/Index', [
            'payrolls'      => $payrolls,
            'currentPeriod' => $period,
        ]);
    }

    /**
     * Generate payroll untuk semua karyawan di period tertentu
     * Kalkulasi otomatis BPJS & PPh 21 untuk semua 5 kategori
     */
    public function generate(Request $request)
    {
        $request->validate(['period' => 'required|string']);
        $teamId = $request->user()->current_team_id;
        $period = $request->period;

        $employees = Employee::where('team_id', $teamId)->get();

        // Ambil ringkasan absensi dari AttendanceController
        $attendanceSummary = AttendanceController::getSummaryForPeriod($teamId, $period);

        foreach ($employees as $emp) {
            $existing = Payroll::where('employee_id', $emp->id)
                ->where('period', $period)
                ->first();

            // Prioritaskan data absensi nyata, fallback ke data existing/default
            $absensi   = $attendanceSummary[$emp->id] ?? null;
            $hariKerja = $absensi ? (int) $absensi['hari_kerja'] : ($existing->hari_kerja ?? 30);
            $totalOt   = $absensi ? (float) $absensi['total_ot'] : ($existing->ot_hari_biasa ?? 0);

            $inputs = [
                'base_salary'        => $emp->base_salary,
                'daily_rate'         => $emp->daily_rate,
                'tax_status'         => $emp->tax_status,
                'hari_kerja'         => $hariKerja,
                'uang_jabatan'       => $existing->uang_jabatan ?? 0,
                'tunjangan_prestasi' => $existing->tunjangan_prestasi ?? 0,
                'tunjangan_insentif' => $existing->tunjangan_insentif ?? 0,
                'tunjangan_bonus'    => $existing->tunjangan_bonus ?? 0,
                'ot_hari_biasa'      => $totalOt,
                'ot_hari_libur'      => $existing->ot_hari_libur ?? 0,
                'uang_makan'         => $existing->uang_makan ?? 0,
                'hari_makan'         => $existing->hari_makan ?? $hariKerja,
                'uang_makan_harian'  => $existing->uang_makan_harian ?? $this->getDefaultMealRate($emp->payroll_category),
                'kas_bon'            => $existing->kas_bon ?? 0,
                'pph21'              => ($emp->payroll_category === 'Direksi') ? ($existing->pph21 ?? 0) : 0,
                'jkk_jkm'           => ($emp->payroll_category === 'Direksi') ? ($existing->jkk_jkm ?? 595000) : 0,
                'jht_company'       => ($emp->payroll_category === 'Direksi') ? ($existing->jht_company ?? 2850000) : 0,
                'jp_company'        => ($emp->payroll_category === 'Direksi') ? ($existing->jp_company ?? 1000000) : 0,
                'bpjs_kes_company'  => ($emp->payroll_category === 'Direksi') ? ($existing->bpjs_kes_company ?? 600000) : 0,
            ];

            $result = $this->calculator->calculate($emp->payroll_category, $inputs);

            Payroll::updateOrCreate(
                ['employee_id' => $emp->id, 'period' => $period],
                array_merge($result, ['team_id' => $teamId, 'status' => 'calculated'])
            );
        }

        return Inertia::location(route('payroll.index', ['period' => $period]));
    }

    /**
     * Tampilkan detail payroll untuk satu kategori di periode tertentu
     */
    public function show(Request $request, string $period, string $category)
    {
        $allowedCategories = ['Direksi', 'Staff', 'General', 'Produksi 1', 'Produksi 2'];
        if (!in_array($category, $allowedCategories)) {
            abort(404);
        }

        $payrolls = Payroll::with('employee')
            ->where('team_id', $request->user()->current_team_id)
            ->where('period', $period)
            ->whereHas('employee', function ($query) use ($category) {
                $query->where('payroll_category', $category);
            })
            ->get();

        return Inertia::render('Payroll/Show', [
            'payrolls'      => $payrolls,
            'currentPeriod' => $period,
            'category'      => $category,
        ]);
    }

    /**
     * Update detail payroll individual (Inertia, untuk PATCH /payroll/{id})
     */
    public function update(Request $request, Payroll $payroll)
    {
        $emp = $payroll->employee;

        $editableFields = $request->only([
            'hari_kerja', 'uang_jabatan', 'tunjangan_prestasi',
            'tunjangan_insentif', 'tunjangan_bonus',
            'ot_hari_biasa', 'ot_hari_libur',
            'uang_makan', 'hari_makan', 'uang_makan_harian',
            'kas_bon', 'salary_pokok', 'pph21',
            'jkk_jkm', 'jht_company', 'jp_company', 'bpjs_kes_company',
        ]);

        $inputs = [
            'base_salary'        => $editableFields['salary_pokok'] ?? $emp->base_salary,
            'daily_rate'         => $emp->daily_rate,
            'tax_status'         => $emp->tax_status,
            'hari_kerja'         => $editableFields['hari_kerja'] ?? $payroll->hari_kerja ?? 30,
            'uang_jabatan'       => $editableFields['uang_jabatan'] ?? $payroll->uang_jabatan ?? 0,
            'tunjangan_prestasi' => $editableFields['tunjangan_prestasi'] ?? $payroll->tunjangan_prestasi ?? 0,
            'tunjangan_insentif' => $editableFields['tunjangan_insentif'] ?? $payroll->tunjangan_insentif ?? 0,
            'tunjangan_bonus'    => $editableFields['tunjangan_bonus'] ?? $payroll->tunjangan_bonus ?? 0,
            'ot_hari_biasa'      => $editableFields['ot_hari_biasa'] ?? $payroll->ot_hari_biasa ?? 0,
            'ot_hari_libur'      => $editableFields['ot_hari_libur'] ?? $payroll->ot_hari_libur ?? 0,
            'uang_makan'         => $editableFields['uang_makan'] ?? $payroll->uang_makan ?? 0,
            'hari_makan'         => $editableFields['hari_makan'] ?? $payroll->hari_makan ?? 0,
            'uang_makan_harian'  => $editableFields['uang_makan_harian'] ?? $payroll->uang_makan_harian ?? 0,
            'kas_bon'            => $editableFields['kas_bon'] ?? $payroll->kas_bon ?? 0,
            'pph21'              => ($emp->payroll_category === 'Direksi') ? ($editableFields['pph21'] ?? $payroll->pph21 ?? 0) : 0,
            'jkk_jkm'           => $editableFields['jkk_jkm'] ?? $payroll->jkk_jkm ?? 0,
            'jht_company'       => $editableFields['jht_company'] ?? $payroll->jht_company ?? 0,
            'jp_company'        => $editableFields['jp_company'] ?? $payroll->jp_company ?? 0,
            'bpjs_kes_company'  => $editableFields['bpjs_kes_company'] ?? $payroll->bpjs_kes_company ?? 0,
        ];

        $result = $this->calculator->calculate($emp->payroll_category, $inputs);
        $payroll->fill(array_merge($result, ['status' => 'calculated']));
        $payroll->save();

        return Inertia::location(route('payroll.index', ['period' => $payroll->period]));
    }

    /**
     * AJAX update detail for inline edit in Vue table (returns JSON)
     */
    public function updateDetail(Request $request, Payroll $payroll)
    {
        $emp = $payroll->employee;

        $inputs = [
            'base_salary'        => $request->salary_pokok ?? $payroll->salary_pokok ?? $emp->base_salary,
            'daily_rate'         => $emp->daily_rate,
            'tax_status'         => $emp->tax_status,
            'hari_kerja'         => $request->hari_kerja ?? $payroll->hari_kerja ?? 30,
            'uang_jabatan'       => $request->uang_jabatan ?? $payroll->uang_jabatan ?? 0,
            'tunjangan_prestasi' => $request->tunjangan_prestasi ?? $payroll->tunjangan_prestasi ?? 0,
            'tunjangan_insentif' => $request->tunjangan_insentif ?? $payroll->tunjangan_insentif ?? 0,
            'tunjangan_bonus'    => $request->tunjangan_bonus ?? $payroll->tunjangan_bonus ?? 0,
            'ot_hari_biasa'      => $request->ot_hari_biasa ?? $payroll->ot_hari_biasa ?? 0,
            'ot_hari_libur'      => $request->ot_hari_libur ?? $payroll->ot_hari_libur ?? 0,
            'uang_makan'         => $request->uang_makan ?? $payroll->uang_makan ?? 0,
            'hari_makan'         => $request->hari_makan ?? $payroll->hari_makan ?? 0,
            'uang_makan_harian'  => $request->uang_makan_harian ?? $payroll->uang_makan_harian ?? 0,
            'kas_bon'            => $request->kas_bon ?? $payroll->kas_bon ?? 0,
            'pph21'              => ($emp->payroll_category === 'Direksi') ? ($request->pph21 ?? $payroll->pph21 ?? 0) : 0,
            'jkk_jkm'           => $request->jkk_jkm ?? $payroll->jkk_jkm ?? 0,
            'jht_company'       => $request->jht_company ?? $payroll->jht_company ?? 0,
            'jp_company'        => $request->jp_company ?? $payroll->jp_company ?? 0,
            'bpjs_kes_company'  => $request->bpjs_kes_company ?? $payroll->bpjs_kes_company ?? 0,
        ];

        $result = $this->calculator->calculate($emp->payroll_category, $inputs);
        $payroll->fill(array_merge($result, ['status' => 'calculated']));
        $payroll->save();

        return response()->json(['payroll' => $payroll->fresh()->load('employee')]);
    }

    /**
     * Recalculate a single payroll (Inertia or JSON response)
     */
    public function recalculate(Request $request, Payroll $payroll)
    {
        $emp = $payroll->employee;

        $inputs = [
            'base_salary'        => $payroll->salary_pokok ?? $emp->base_salary,
            'daily_rate'         => $emp->daily_rate,
            'tax_status'         => $emp->tax_status,
            'hari_kerja'         => $payroll->hari_kerja ?? 30,
            'uang_jabatan'       => $payroll->uang_jabatan ?? 0,
            'tunjangan_prestasi' => $payroll->tunjangan_prestasi ?? 0,
            'tunjangan_insentif' => $payroll->tunjangan_insentif ?? 0,
            'tunjangan_bonus'    => $payroll->tunjangan_bonus ?? 0,
            'ot_hari_biasa'      => $payroll->ot_hari_biasa ?? 0,
            'ot_hari_libur'      => $payroll->ot_hari_libur ?? 0,
            'uang_makan'         => $payroll->uang_makan ?? 0,
            'hari_makan'         => $payroll->hari_makan ?? 0,
            'uang_makan_harian'  => $payroll->uang_makan_harian ?? 0,
            'kas_bon'            => $payroll->kas_bon ?? 0,
            'pph21'              => ($emp->payroll_category === 'Direksi') ? ($payroll->pph21 ?? 0) : 0,
            'jkk_jkm'           => $payroll->jkk_jkm ?? 0,
            'jht_company'       => $payroll->jht_company ?? 0,
            'jp_company'        => $payroll->jp_company ?? 0,
            'bpjs_kes_company'  => $payroll->bpjs_kes_company ?? 0,
        ];

        $result = $this->calculator->calculate($emp->payroll_category, $inputs);
        $payroll->fill(array_merge($result, ['status' => 'calculated']));
        $payroll->save();

        if ($request->expectsJson()) {
            return response()->json(['payroll' => $payroll->fresh()]);
        }

        return Redirect::back()->with('message', 'Payroll berhasil dihitung ulang!');
    }

    /**
     * Show slip gaji individual
     */
    public function slip(Payroll $payroll)
    {
        $payroll->load('employee');

        return Inertia::render('Payroll/Slip', [
            'payroll' => $payroll,
        ]);
    }

    /**
     * Hapus Data Payroll Satuan
     */
    public function destroy(Payroll $payroll)
    {
        if ($payroll->team_id === auth()->user()->current_team_id) {
            $payroll->delete();
        }

        return Redirect::back()->with('message', 'Data payroll dihapus.');
    }

    /**
     * Default uang makan harian per kategori
     */
    private function getDefaultMealRate(string $category): float
    {
        return match ($category) {
            'Produksi 2' => 4000,
            'General'    => 6000,
            default      => 4000,
        };
    }
}