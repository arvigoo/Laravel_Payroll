<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkSetting extends Model
{
    protected $fillable = [
        'team_id',
        'check_in_start',
        'check_in_end',
        'check_out_start',
        'work_end',
        'ot_multiplier',
    ];

    /**
     * Get or create default work settings for a team
     */
    public static function forTeam(int $teamId): self
    {
        return self::firstOrCreate(
            ['team_id' => $teamId],
            [
                'check_in_start'  => '07:00:00',
                'check_in_end'    => '09:00:00',
                'check_out_start' => '16:00:00',
                'work_end'        => '17:00:00',
                'ot_multiplier'   => 2,
            ]
        );
    }
}
