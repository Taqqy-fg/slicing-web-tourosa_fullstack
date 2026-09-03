<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ──────────────── PERMISSIONS ────────────────
        $permissionsData = [
            // Dashboard
            ['name' => 'dashboard.view', 'label' => 'Lihat Dashboard', 'group' => 'Dashboard'],

            // Orders
            ['name' => 'orders.view', 'label' => 'Lihat Pesanan', 'group' => 'Pesanan'],
            ['name' => 'orders.create', 'label' => 'Buat Pesanan', 'group' => 'Pesanan'],
            ['name' => 'orders.update', 'label' => 'Ubah Pesanan', 'group' => 'Pesanan'],
            ['name' => 'orders.delete', 'label' => 'Hapus Pesanan', 'group' => 'Pesanan'],
            ['name' => 'orders.payment', 'label' => 'Catat Pembayaran', 'group' => 'Pesanan'],

            // Reports
            ['name' => 'reports.view', 'label' => 'Lihat Laporan', 'group' => 'Laporan'],
            ['name' => 'reports.export', 'label' => 'Export Laporan', 'group' => 'Laporan'],

            // Settings
            ['name' => 'settings.view', 'label' => 'Lihat Pengaturan', 'group' => 'Pengaturan'],
            ['name' => 'settings.update', 'label' => 'Ubah Pengaturan', 'group' => 'Pengaturan'],

            // Catalog
            ['name' => 'catalog.view', 'label' => 'Lihat Katalog', 'group' => 'Katalog'],
            ['name' => 'catalog.update', 'label' => 'Ubah Katalog', 'group' => 'Katalog'],

            // Testimonials
            ['name' => 'testimonials.view', 'label' => 'Lihat Testimonial', 'group' => 'Testimonial'],
            ['name' => 'testimonials.create', 'label' => 'Buat Testimonial', 'group' => 'Testimonial'],
            ['name' => 'testimonials.update', 'label' => 'Ubah Testimonial', 'group' => 'Testimonial'],
            ['name' => 'testimonials.delete', 'label' => 'Hapus Testimonial', 'group' => 'Testimonial'],

            // Admin Management
            ['name' => 'admins.view', 'label' => 'Lihat Daftar Admin', 'group' => 'Admin'],
            ['name' => 'admins.create', 'label' => 'Buat Akun Admin', 'group' => 'Admin'],
            ['name' => 'admins.update', 'label' => 'Ubah Akun Admin', 'group' => 'Admin'],
            ['name' => 'admins.delete', 'label' => 'Hapus Akun Admin', 'group' => 'Admin'],

            // Roles & Permissions
            ['name' => 'roles.view', 'label' => 'Lihat Role', 'group' => 'Role & Permission'],
            ['name' => 'roles.create', 'label' => 'Buat Role', 'group' => 'Role & Permission'],
            ['name' => 'roles.update', 'label' => 'Ubah Role', 'group' => 'Role & Permission'],
            ['name' => 'roles.delete', 'label' => 'Hapus Role', 'group' => 'Role & Permission'],
            ['name' => 'permissions.view', 'label' => 'Lihat Permission', 'group' => 'Role & Permission'],
            ['name' => 'permissions.manage', 'label' => 'Kelola Permission', 'group' => 'Role & Permission'],
        ];

        foreach ($permissionsData as $p) {
            Permission::updateOrCreate(['name' => $p['name']], $p);
        }

        // ──────────────── ROLES ────────────────
        $allPermissions = Permission::pluck('id')->toArray();

        // Super Admin — has everything (is_superadmin flag also bypasses checks)
        $superAdmin = Role::updateOrCreate(
            ['name' => 'super_admin'],
            ['label' => 'Super Admin', 'description' => 'Akses penuh ke seluruh fitur sistem.', 'is_system' => true]
        );
        $superAdmin->permissions()->sync($allPermissions);

        // Admin — full operational access, can view roles but not create/edit/delete
        $admin = Role::updateOrCreate(
            ['name' => 'admin'],
            ['label' => 'Admin', 'description' => 'Akses penuh untuk operasional harian.', 'is_system' => true]
        );
        $adminPermNames = [
            'dashboard.view',
            'orders.view', 'orders.create', 'orders.update', 'orders.delete', 'orders.payment',
            'reports.view', 'reports.export',
            'catalog.view', 'catalog.update',
            'testimonials.view', 'testimonials.create', 'testimonials.update', 'testimonials.delete',
        ];
        $adminPermIds = Permission::whereIn('name', $adminPermNames)->pluck('id')->toArray();
        $admin->permissions()->sync($adminPermIds);

        // ──────────────── SEED USERS WITH ROLES ────────────────
        // Main super admin
        $superAdminUser = User::firstOrCreate(
            ['email' => 'admin@tourosa.id'],
            [
                'name' => 'Admin Tourosa',
                'password' => Hash::make('password'),
            ]
        );
        $superAdminUser->is_superadmin = true;
        $superAdminUser->save();
        $superAdminUser->roles()->syncWithoutDetaching([$superAdmin->id]);

        // Sample admin user
        $adminUser = User::firstOrCreate(
            ['email' => 'manager@tourosa.id'],
            [
                'name' => 'Manager Tourosa',
                'password' => Hash::make('password'),
            ]
        );
        $adminUser->is_superadmin = false;
        $adminUser->save();
        $adminUser->roles()->syncWithoutDetaching([$admin->id]);

    }
}
