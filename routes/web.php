<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\WorkSettingController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'role:admin',
])->group(function () {
    // === EMPLOYEES ===
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::put('/employees/{employee}/account', [EmployeeController::class, 'updateAccount'])->name('employees.updateAccount');
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');

    // === PAYROLL ===
    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('/payroll/show/{period}/{category}', [PayrollController::class, 'show'])->name('payroll.show');
    Route::post('/payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');

    // Specific routes BEFORE {payroll} wildcard to prevent conflicts
    Route::patch('/payroll/{payroll}/detail', [PayrollController::class, 'updateDetail'])->name('payroll.detail.update');
    Route::post('/payroll/{payroll}/recalculate', [PayrollController::class, 'recalculate'])->name('payroll.recalculate');
    Route::get('/payroll/{payroll}/slip', [PayrollController::class, 'slip'])->name('payroll.slip');

    Route::patch('/payroll/{payroll}', [PayrollController::class, 'update'])->name('payroll.update');
    Route::delete('/payroll/{payroll}', [PayrollController::class, 'destroy'])->name('payroll.destroy');

    // === ATTENDANCE (Admin) ===
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::delete('/attendance/{attendance}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');

    // === WORK SETTINGS ===
    Route::get('/settings/work', [WorkSettingController::class, 'index'])->name('settings.work');
    Route::put('/settings/work', [WorkSettingController::class, 'update'])->name('settings.work.update');
});

// === ATTENDANCE (Karyawan) — no admin role required ===
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/absen', [AttendanceController::class, 'myAttendance'])->name('attendance.my');
    Route::post('/absen/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.checkin');
    Route::post('/absen/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.checkout');
});