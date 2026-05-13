<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class PayrollController extends Controller
{
    /**
     * Tampilkan Daftar Payroll per Bulan
     */
    public function index(Request $request)
    {
        $period = $request->query('period', date('Y-m'));
        
        $payrolls = Payroll::with('employee')
            ->where('team_id', $request->user()->current_team_id)
            ->where('period', $period)
            ->get();

        return Inertia::render('Payroll/Index', [
            'payrolls' => $payrolls,
            'currentPeriod' => $period
        ]);
    }

    /**
     * Logic Kalkulasi Gaji (Staff Otomatis, Direksi Manual-Ready)
     */
    public function generate(Request $request)
    {
        $request->validate(['period' => 'required|string']);
        $teamId = $request->user()->current_team_id;
        $period = $request->period;

        $employees = Employee::where('team_id', $teamId)->get();

        foreach ($employees as $emp) {
            if ($emp->payroll_category === 'Direksi') {
                // LOGIC DIREKSI: Sesuai request, Tunjangan & BPJS Karyawan = 0
                // Gaji pokok diambil dari master, sisanya biar diinput manual di tabel
                $data = [
                    'salary_pokok' => $emp->base_salary,
                    'allowances' => 0,
                    'overtime' => 0,
                    'bpjs_employee' => 0,
                    'pph21' => 0, // Biar diisi manual oleh admin
                    'net_salary' => $emp->base_salary,
                ];
            } else {
                // LOGIC STAFF: Otomatis dengan rumus standar
                $gajiPokok = $emp->base_salary;
                $tunjangan = 500000; 
                $totalGaji = $gajiPokok + $tunjangan;

                // BPJS Ditanggung Karyawan (Misal total 3%)
                $bpjsEmployee = $totalGaji * 0.03;

                // Simulasi PPh 21 TER
                $pph21 = ($totalGaji > 5400000) ? ($totalGaji * 0.02) : 0;

                $data = [
                    'salary_pokok' => $gajiPokok,
                    'allowances' => $tunjangan,
                    'overtime' => 0,
                    'bpjs_employee' => $bpjsEmployee,
                    'pph21' => $pph21,
                    'net_salary' => $totalGaji - ($bpjsEmployee + $pph21),
                ];
            }

            Payroll::updateOrCreate(
                ['employee_id' => $emp->id, 'period' => $period],
                array_merge($data, ['team_id' => $teamId])
            );
        }

        return Redirect::back()->with('message', 'Payroll berhasil di-generate!');
    }

    /**
     * Update Manual untuk Gaji Direksi (Editable Table)
     */
    public function update(Request $request, Payroll $payroll)
    {
        // 1. Security Check
        if ($payroll->team_id !== $request->user()->current_team_id) {
            abort(403);
        }

        // 2. Validation
        $validated = $request->validate([
            'salary_pokok' => 'required|numeric',
            'pph21' => 'required|numeric',
        ]);

        // 3. Update Data secara Explicit
        $payroll->update([
            'salary_pokok' => $request->salary_pokok,
            'pph21' => $request->pph21,
            // Net Salary langsung dihitung di sini biar sinkron
            'net_salary' => $request->salary_pokok - $request->pph21,
        ]);

        // 4. Gunakan Inertia::location() untuk redirect aman dari request Inertia
        return Inertia::location(route('payroll.index', ['period' => $payroll->period]));
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
}