<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private array $permissions = [
        'dashboard.view',

        'users.view',
        'users.create',
        'users.update',
        'users.delete',

        'roles.view',
        'roles.create',
        'roles.update',
        'roles.delete',

        'audits.view',

        'specialties.view',
        'specialties.create',
        'specialties.update',
        'specialties.delete',

        'medicines.view',
        'medicines.create',
        'medicines.update',
        'medicines.delete',

        'patients.view',
        'patients.create',
        'patients.update',
        'patients.delete',

        'doctors.view',
        'doctors.create',
        'doctors.update',
        'doctors.delete',

        'appointments.view',
        'appointments.create',
        'appointments.reschedule',
        'appointments.cancel',

        'consultations.view',
        'consultations.create',
        'consultations.update',
        'consultations.finalize',

        'prescriptions.view',
        'prescriptions.create',
        'prescriptions.cancel',
        'prescriptions.export',

        'payments.view',
        'payments.create',

        'reports.view',
        'reports.export',

        'settings.view',
        'settings.update',
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $all = Permission::query()->where('guard_name', 'web')->pluck('name')->all();

        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions($all);

        $doctor = Role::findOrCreate('doctor', 'web');
        $doctor->syncPermissions([
            'dashboard.view',
            'specialties.view',
            'medicines.view',
            'patients.view',
            'patients.create',
            'patients.update',
            'doctors.view',
            'appointments.view',
            'consultations.view',
            'consultations.create',
            'consultations.update',
            'consultations.finalize',
            'prescriptions.view',
            'prescriptions.create',
            'prescriptions.cancel',
            'prescriptions.export',
            'payments.view',
            'reports.view',
        ]);

        $receptionist = Role::findOrCreate('receptionist', 'web');
        $receptionist->syncPermissions([
            'dashboard.view',
            'specialties.view',
            'medicines.view',
            'patients.view',
            'patients.create',
            'patients.update',
            'doctors.view',
            'appointments.view',
            'appointments.create',
            'appointments.reschedule',
            'appointments.cancel',
            'payments.view',
            'payments.create',
            'reports.view',
        ]);
    }
}
