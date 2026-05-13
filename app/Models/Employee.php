<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'payroll_category'
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
    ];
}