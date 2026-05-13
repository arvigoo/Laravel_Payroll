<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('payroll_category')->default('Staff')->after('position'); // Staff, Direksi, dll
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};
