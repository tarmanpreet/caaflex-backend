<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with roles and permissions.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions
        $permissions = [
            // Client management
            'clients.view-any',
            'clients.create',
            'clients.update',
            'clients.delete',
            'clients.view-own',

            // Document management
            'documents.upload',
            'documents.download',
            'documents.delete',

            // User management
            'users.view-any',
            'users.create',
            'users.update',
            'users.delete',

            // Admin management
            'admins.create',
            'admins.delete',

            // Practice management
            'practices.view-any',
            'practices.view-own',
            'practices.create',
            'practices.update',
            'practices.delete',
            'practices.assign',

            // Practice document management
            'practice-documents.upload',
            'practice-documents.download',
            'practice-documents.delete',

            // Practice note management
            'practice-notes.create',

            // Practice deadline management
            'practice-deadlines.view',
            'practice-deadlines.create',
            'practice-deadlines.update',
            'practice-deadlines.delete',

            // Appointment management
            'appointments.view-any',
            'appointments.view-own',
            'appointments.create',
            'appointments.update',
            'appointments.delete',
            'appointments.assign',

            // Practice type management
            'practice-types.view-any',
            'practice-types.create',
            'practice-types.update',
            'practice-types.delete',

            // Procedure management
            'procedures.view-any',
            'procedures.create',
            'procedures.update',
            'procedures.delete',

            // User availability management
            'user-availabilities.manage',

            // Auto confirm slot management
            'auto-confirm-slots.manage',

            // Branch management
            'branches.view-any',
            'branches.create',
            'branches.update',
            'branches.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $rolePermissions = [
            'superadmin' => $permissions,
            'admin' => [
                'clients.view-any',
                'clients.create',
                'clients.update',
                'clients.delete',
                'clients.view-own',
                'documents.upload',
                'documents.download',
                'documents.delete',
                'users.view-any',
                'users.create',
                'users.update',
                'users.delete',
                'practices.view-any',
                'practices.view-own',
                'practices.create',
                'practices.update',
                'practices.delete',
                'practices.assign',
                'practice-documents.upload',
                'practice-documents.download',
                'practice-documents.delete',
                'practice-notes.create',
                'practice-deadlines.view',
                'practice-deadlines.create',
                'practice-deadlines.update',
                'practice-deadlines.delete',
                'appointments.view-any',
                'appointments.view-own',
                'appointments.create',
                'appointments.update',
                'appointments.delete',
                'appointments.assign',
                'practice-types.view-any',
                'practice-types.create',
                'practice-types.update',
                'practice-types.delete',
                'procedures.view-any',
                'procedures.create',
                'procedures.update',
                'procedures.delete',
                'user-availabilities.manage',
                'auto-confirm-slots.manage',
                'branches.view-any',
                'branches.create',
                'branches.update',
                'branches.delete',
            ],
            'employee' => [
                'clients.view-any',
                'clients.create',
                'clients.update',
                'clients.view-own',
                'documents.upload',
                'documents.download',
                'practices.view-own',
                'practices.create',
                'practices.update',
                'practice-documents.upload',
                'practice-documents.download',
                'practice-notes.create',
                'practice-deadlines.view',
                'practice-deadlines.create',
                'practice-deadlines.update',
                'appointments.view-own',
                'appointments.create',
                'practice-types.view-any',
                'procedures.view-any',
            ],
            'cliente' => [
                'clients.view-own',
                'documents.download',
                'appointments.view-own',
                'appointments.create',
                'appointments.delete',
            ],
        ];

        foreach ($rolePermissions as $roleName => $rolePerms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions(
                Permission::whereIn('name', $rolePerms)->where('guard_name', 'web')->get()
            );
        }

        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
