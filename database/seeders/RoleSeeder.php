<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
// INI YANG PENTING, BOS:
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache Spatie (best practice biar nggak nyangkut)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Buat Role
        $admin = Role::create(['name' => 'admin']);
        $employee = Role::create(['name' => 'employee']);

        // Buat Permission
        Permission::create(['name' => 'manage payroll']);
        Permission::create(['name' => 'view own payroll']);

        // Kasih Permission ke Role
        $admin->givePermissionTo('manage payroll');
        $employee->givePermissionTo('view own payroll');
    }
}