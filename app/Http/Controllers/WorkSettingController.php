<?php

namespace App\Http\Controllers;

use App\Models\WorkSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WorkSettingController extends Controller
{
    public function index(Request $request)
    {
        $setting = WorkSetting::forTeam($request->user()->current_team_id);

        return Inertia::render('Settings/Work', [
            'setting' => $setting,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'check_in_start'  => 'required|date_format:H:i',
            'check_in_end'    => 'required|date_format:H:i',
            'check_out_start' => 'required|date_format:H:i',
            'work_end'        => 'required|date_format:H:i',
            'ot_multiplier'   => 'required|integer|min:1|max:4',
        ]);

        $setting = WorkSetting::forTeam($request->user()->current_team_id);
        $setting->update($validated);

        return back()->with('success', 'Pengaturan jam kerja disimpan.');
    }
}
