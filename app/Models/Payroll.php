<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    protected $fillable = [
        'employee_id', 'team_id', 'period', 
        'salary_pokok', 'allowances', 'overtime', 
        'jkk_jkm', 'jht_company', 'jp_company', 'bpjs_kes_company',
        'bpjs_employee', 'pph21', 'net_salary'
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}