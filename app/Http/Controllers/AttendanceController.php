<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\WorkSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    /**
     * Admin: Rekap semua absensi
     */
    public function index(Request $request)
    {
        $period = $request->query('period', date('Y-m'));
        $teamId = $request->user()->current_team_id;

        [$year, $month] = explode('-', $period);

        $attendances = Attendance::with('employee')
            ->where('team_id', $teamId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'desc')
            ->orderBy('employee_id')
            ->get()
            ->map(function ($a) {
                return array_merge($a->toArray(), [
                    'check_in_photo_url'  => $a->check_in_photo_url,
                    'check_out_photo_url' => $a->check_out_photo_url,
                ]);
            });

        $setting = WorkSetting::forTeam($teamId);

        // Summary per karyawan
        $summary = Attendance::where('team_id', $teamId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->selectRaw('employee_id, count(*) as hari_kerja, sum(ot_hours) as total_ot, sum(status = "late") as hari_terlambat')
            ->groupBy('employee_id')
            ->get();

        return Inertia::render('Attendance/Index', [
            'attendances'   => $attendances,
            'summary'       => $summary,
            'currentPeriod' => $period,
            'setting'       => $setting,
        ]);
    }

    /**
     * Karyawan: Halaman absen sendiri (check-in / check-out)
     */
    public function myAttendance(Request $request)
    {
        $user = $request->user();

        // Cari employee yang terhubung ke user ini (tanpa filter team agar lebih fleksibel)
        $employee = Employee::where('user_id', $user->id)->first();

        // Resolve teamId: dari employee, atau current_team_id, fallback ke team pertama user
        $teamId = $employee?->team_id
            ?? $user->current_team_id
            ?? $user->teams()->first()?->id;

        // Sync current_team_id jika belum di-set
        if ($teamId && !$user->current_team_id) {
            $user->forceFill(['current_team_id' => $teamId])->save();
        }

        $today       = now()->toDateString();
        $setting     = WorkSetting::forTeam((int) $teamId);
        $todayRecord = null;

        if ($employee) {
            $todayRecord = Attendance::where('employee_id', $employee->id)
                ->where('date', $today)
                ->first();

            if ($todayRecord) {
                $todayRecord->check_in_photo_url  = $todayRecord->check_in_photo_url;
                $todayRecord->check_out_photo_url = $todayRecord->check_out_photo_url;
            }
        }

        // Riwayat 7 hari terakhir
        $history = $employee
            ? Attendance::where('employee_id', $employee->id)
                ->orderBy('date', 'desc')
                ->limit(7)
                ->get()
                ->map(fn($a) => array_merge($a->toArray(), [
                    'check_in_photo_url'  => $a->check_in_photo_url,
                    'check_out_photo_url' => $a->check_out_photo_url,
                ]))
            : [];

        return Inertia::render('Attendance/CheckIn', [
            'employee'    => $employee,
            'todayRecord' => $todayRecord,
            'setting'     => $setting,
            'today'       => $today,
            'history'     => $history,
        ]);
    }

    /**
     * Karyawan: Proses Check-In
     */
    public function checkIn(Request $request)
    {
        $user     = $request->user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();
        $teamId   = (int) ($employee->team_id ?? $user->current_team_id);

        $today   = now()->toDateString();
        $nowTime = now()->format('H:i:s');

        // Cegah double check-in
        $existing = Attendance::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        if ($existing && $existing->check_in) {
            return back()->with('error', 'Anda sudah melakukan check-in hari ini.');
        }

        $request->validate(['photo' => 'required|string']); // base64

        // Simpan foto
        $photoPath = $this->saveBase64Photo($request->photo, 'checkin', $employee->nik, $today);

        // Tentukan status (terlambat atau tidak)
        $setting = WorkSetting::forTeam($teamId);
        $status  = $nowTime > $setting->check_in_end ? 'late' : 'present';

        Attendance::updateOrCreate(
            ['employee_id' => $employee->id, 'date' => $today],
            [
                'team_id'        => $teamId,
                'check_in'       => $nowTime,
                'check_in_photo' => $photoPath,
                'status'         => $status,
            ]
        );

        return back()->with('success', 'Check-in berhasil! Selamat bekerja 🎉');
    }

    /**
     * Karyawan: Proses Check-Out
     */
    public function checkOut(Request $request)
    {
        $user     = $request->user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();
        $teamId   = (int) ($employee->team_id ?? $user->current_team_id);

        $today   = now()->toDateString();
        $nowTime = now()->format('H:i:s');

        $record = Attendance::where('employee_id', $employee->id)
            ->where('date', $today)
            ->firstOrFail();

        if ($record->check_out) {
            return back()->with('error', 'Anda sudah melakukan check-out hari ini.');
        }

        $request->validate(['photo' => 'required|string']); // base64

        $photoPath = $this->saveBase64Photo($request->photo, 'checkout', $employee->nik, $today);

        // Hitung OT: waktu setelah jam kerja berakhir
        $setting  = WorkSetting::forTeam($teamId);
        $workEnd  = Carbon::parse($today . ' ' . $setting->work_end);
        $checkOut = Carbon::parse($today . ' ' . $nowTime);
        $otHours  = $checkOut->gt($workEnd) ? round($checkOut->diffInMinutes($workEnd) / 60, 2) : 0;

        $record->update([
            'check_out'       => $nowTime,
            'check_out_photo' => $photoPath,
            'ot_hours'        => $otHours,
        ]);

        $msg = $otHours > 0
            ? "Check-out berhasil! Lembur {$otHours} jam tercatat ✅"
            : 'Check-out berhasil! Sampai jumpa besok 👋';

        return back()->with('success', $msg);
    }

    /**
     * Admin: Hapus data absensi
     */
    public function destroy(Attendance $attendance)
    {
        if ($attendance->team_id !== auth()->user()->current_team_id) {
            abort(403);
        }

        // Hapus foto dari storage
        if ($attendance->check_in_photo)  Storage::disk('public')->delete($attendance->check_in_photo);
        if ($attendance->check_out_photo) Storage::disk('public')->delete($attendance->check_out_photo);

        $attendance->delete();

        return back()->with('success', 'Data absensi dihapus.');
    }

    /**
     * Simpan foto base64 ke storage/public/attendance/
     */
    private function saveBase64Photo(string $base64, string $type, string $nik, string $date): string
    {
        // Strip data URI prefix: "data:image/jpeg;base64,..."
        $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
        $decoded   = base64_decode($imageData);

        $filename = "attendance/{$nik}_{$date}_{$type}.jpg";
        Storage::disk('public')->put($filename, $decoded);

        return $filename;
    }

    /**
     * Ringkasan absensi per karyawan per periode — digunakan PayrollController
     */
    public static function getSummaryForPeriod(int $teamId, string $period): array
    {
        [$year, $month] = explode('-', $period);

        $rows = Attendance::where('team_id', $teamId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereNotNull('check_in')
            ->selectRaw('employee_id, count(*) as hari_kerja, sum(ot_hours) as total_ot')
            ->groupBy('employee_id')
            ->get();

        return $rows->keyBy('employee_id')->toArray();
    }
}
