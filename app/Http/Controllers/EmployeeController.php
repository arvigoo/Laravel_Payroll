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
        $query = Employee::with('user')
            ->where('team_id', $request->user()->current_team_id)
            ->orderBy('name');

        return Inertia::render('Employees/Index', [
            'employees' => $query->paginate(15),
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
        $categories = 'Direksi,Staff,General,Produksi 1,Produksi 2';

        $validated = $request->validate([
            'nik' => [
                'required', 
                'string', 
                Rule::unique('employees')->where(fn ($query) => $query->where('team_id', $request->user()->current_team_id))
            ],
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'payroll_category' => "required|string|in:$categories",
            'tax_status' => 'required|string|in:TK,K,K1,K2,K3',
            'base_salary' => 'required|numeric|min:0',
            'daily_rate' => 'nullable|numeric|min:0',
            'masa_kerja' => 'nullable|integer|min:0',
        ]);

        $teamId = $request->user()->current_team_id;

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $teamId) {
            // Auto create user for the employee
            $user = \App\Models\User::create([
                'name' => $validated['name'],
                'email' => strtolower($validated['nik']) . '@indobox.com',
                'password' => \Illuminate\Support\Facades\Hash::make('indobox123'),
                'current_team_id' => $teamId,
            ]);

            // Attach user to the team
            $user->teams()->attach($teamId);

            // Create employee record and link to user
            $validated['team_id'] = $teamId;
            $validated['user_id'] = $user->id;
            Employee::create($validated);
        });

        return Inertia::location(route('employees.index'));
    }

    /**
     * Update data karyawan
     */
    public function update(Request $request, Employee $employee)
    {
        $categories = 'Direksi,Staff,General,Produksi 1,Produksi 2';

        $validated = $request->validate([
            'nik' => 'required|string|unique:employees,nik,' . $employee->id,
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'payroll_category' => "required|string|in:$categories",
            'tax_status' => 'required|string|in:TK,K,K1,K2,K3',
            'base_salary' => 'required|numeric',
            'daily_rate' => 'nullable|numeric|min:0',
            'masa_kerja' => 'nullable|integer|min:0',
        ]);

        $employee->update($validated);
        return Inertia::location(route('employees.index'));
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

        return Inertia::location(route('employees.index'));
    }

    /**
     * Update data akun user (Ubah Password)
     */
    public function updateAccount(Request $request, Employee $employee)
    {
        if ($employee->team_id !== $request->user()->current_team_id) {
            abort(403);
        }

        $validated = $request->validate([
            'password' => 'required|string|min:8',
        ]);

        if ($employee->user) {
            $employee->user->update([
                'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            ]);
            return Inertia::location(route('employees.index'))->with('message', 'Password berhasil diubah.');
        }

        return Inertia::location(route('employees.index'))->with('error', 'Karyawan ini tidak memiliki akun user.');
    }
}