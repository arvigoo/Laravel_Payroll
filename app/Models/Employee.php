<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id', 
        'user_id', 
        'nik', 
        'name', 
        'position', 
        'tax_status', 
        'base_salary',
        'daily_rate',
        'masa_kerja',
        'payroll_category',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'daily_rate'  => 'decimal:2',
        'masa_kerja'  => 'integer',
    ];

    /**
     * Relasi ke payrolls
     */
    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Relasi ke user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke team
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Gaji efektif: untuk produksi return daily_rate × 30, untuk lainnya return base_salary
     */
    public function getEffectiveSalaryAttribute(): float
    {
        if (in_array($this->payroll_category, ['Produksi 1', 'Produksi 2']) && $this->daily_rate > 0) {
            return $this->daily_rate * 30;
        }
        return $this->base_salary ?? 0;
    }

    /**
     * Check apakah karyawan kategori produksi (gaji harian)
     */
    public function getIsDailyAttribute(): bool
    {
        return in_array($this->payroll_category, ['Produksi 1', 'Produksi 2']);
    }
}