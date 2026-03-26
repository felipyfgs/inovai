<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $officeUserRole = Role::create(['name' => 'office_user']);
        $companyUserRole = Role::create(['name' => 'company_user']);

        // Create permissions
        $permissions = [
            'manage_offices',
            'manage_plans',
            'manage_users',
            'manage_companies',
            'manage_cadastros',
            'manage_comercial',
            'emit_nfe',
            'emit_nfce',
            'emit_cte',
            'emit_mdfe',
            'manage_estoque',
            'view_reports',
        ];

        foreach ($permissions as $perm) {
            Permission::create(['name' => $perm]);
        }

        $adminRole->givePermissionTo(Permission::all());
        $officeUserRole->givePermissionTo([
            'manage_companies',
            'manage_users',
            'manage_cadastros',
            'manage_comercial',
            'emit_nfe',
            'emit_nfce',
            'emit_cte',
            'emit_mdfe',
            'manage_estoque',
            'view_reports',
        ]);
        $companyUserRole->givePermissionTo([
            'manage_cadastros',
            'manage_comercial',
            'emit_nfe',
            'emit_nfce',
            'manage_estoque',
            'view_reports',
        ]);

        // Create plans
        Plan::create([
            'name' => 'Gratuito',
            'description' => 'Plano gratuito para testes',
            'price' => 0,
            'max_companies' => 1,
            'max_nfs_month' => 10,
            'features' => ['nfe', 'nfce'],
            'is_active' => true,
        ]);

        Plan::create([
            'name' => 'Básico',
            'description' => 'Ideal para pequenos escritórios',
            'price' => 99.90,
            'max_companies' => 5,
            'max_nfs_month' => 100,
            'features' => ['nfe', 'nfce', 'orcamento', 'estoque'],
            'is_active' => true,
        ]);

        Plan::create([
            'name' => 'Profissional',
            'description' => 'Para escritórios em crescimento',
            'price' => 199.90,
            'max_companies' => 20,
            'max_nfs_month' => 500,
            'features' => ['nfe', 'nfce', 'cte', 'mdfe', 'orcamento', 'estoque', 'relatorios'],
            'is_active' => true,
        ]);

        Plan::create([
            'name' => 'Enterprise',
            'description' => 'Para grandes escritórios contábeis',
            'price' => 499.90,
            'max_companies' => 100,
            'max_nfs_month' => 5000,
            'features' => ['nfe', 'nfce', 'cte', 'mdfe', 'orcamento', 'estoque', 'relatorios', 'api', 'suporte_prioritario'],
            'is_active' => true,
        ]);

        // Create admin office and user
        $adminOffice = Office::create([
            'name' => 'InovAI Admin',
            'email' => 'admin@inovai.com.br',
            'type' => 'admin',
        ]);

        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@inovai.com.br',
            'password' => Hash::make('password'),
            'office_id' => $adminOffice->id,
        ]);
        $admin->assignRole('admin');

        // Create demo office and user
        $demoOffice = Office::create([
            'name' => 'Escritório Demo',
            'cnpj' => '12.345.678/0001-00',
            'email' => 'demo@inovai.com.br',
            'type' => 'contador',
        ]);

        $officeUser = User::create([
            'name' => 'Contador Demo',
            'email' => 'contador@inovai.com.br',
            'password' => Hash::make('password'),
            'office_id' => $demoOffice->id,
        ]);
        $officeUser->assignRole('office_user');

        // Create demo subscription
        Subscription::create([
            'office_id' => $demoOffice->id,
            'plan_id' => Plan::where('name', 'Profissional')->first()->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
        ]);
    }
}
