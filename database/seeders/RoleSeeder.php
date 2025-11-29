<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run()
    {
        // permissions (opsional) — buat sesuai kebutuhan
        Permission::firstOrCreate(['name' => 'manage companies']);
        Permission::firstOrCreate(['name' => 'manage jobs']);
        Permission::firstOrCreate(['name' => 'manage applications']);
        Permission::firstOrCreate(['name' => 'manage categories']);

        // roles
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $company = Role::firstOrCreate(['name' => 'company']);
        $jobSeeker = Role::firstOrCreate(['name' => 'job_seeker']);

        // contoh assign permission ke role (opsional)
        $admin->givePermissionTo(Permission::all());
        $company->givePermissionTo(['manage jobs','manage applications']);
        $jobSeeker->givePermissionTo([]); // pelamar default: tidak ada permission admin
    }
}

