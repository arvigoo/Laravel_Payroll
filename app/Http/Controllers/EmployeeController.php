<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    /**
     * Menampilkan daftar karyawan (Master Data)
     */
    public function index(Request $request)
    {
        return Inertia::render('Employees/Index', [
            'employees' => Employee::where('team_id', $request->user()->current_team_id)
                ->orderBy('name')
                ->get(),
            'flash' => [
                'message' => session('message'),
            ],
        ]);
    }

    /**
     * Menyimpan data karyawan baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nik' => [
                'required', 
                'string', 
                Rule::unique('employees')->where(fn ($query) => $query->where('team_id', $request->user()->current_team_id))
            ],
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'payroll_category' => 'required|string|in:Staff,Direksi',
            'tax_status' => 'required|string|in:TK,K1,K2,K3',
            'base_salary' => 'required|numeric|min:0',
        ]);

        // Tambahkan team_id secara otomatis dari user yang login
        $validated['team_id'] = $request->user()->current_team_id;

        Employee::create($validated);

        return Redirect::route('employees.index')->with('message', 'Karyawan berhasil ditambahkan.');
    }

    /**
     * Menghapus data karyawan
     */
    public function destroy(Employee $employee)
    {
        // Pastikan karyawan yang dihapus milik tim yang sama
        if ($employee->team_id !== auth()->user()->current_team_id) {
            abort(403);
        }

        $employee->delete();

        return Redirect::route('employees.index')->with('message', 'Karyawan berhasil dihapus.');
    }
}