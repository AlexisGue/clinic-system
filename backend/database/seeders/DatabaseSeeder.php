<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Doctors\Models\Doctor;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            ClinicCatalogSeeder::class,
            SettingsSeeder::class,
        ]);

        // En producción real: SEED_DEMO_USERS=false. En demo pública (Render): true.
        $seedDemo = filter_var(
            env('SEED_DEMO_USERS', ! app()->isProduction()),
            FILTER_VALIDATE_BOOLEAN
        );

        if (! $seedDemo) {
            $this->command?->warn('SEED_DEMO_USERS desactivado: no se crean usuarios demo.');

            return;
        }

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@demo.test'],
            [
                'name' => 'Administrador',
                'password' => 'Admin1234',
                'is_active' => true,
            ],
        );
        $admin->syncRoles(['admin']);

        $doctorUser = User::query()->updateOrCreate(
            ['email' => 'doctor@demo.test'],
            [
                'name' => 'Dra. Ana Médico',
                'password' => 'Doctor1234',
                'is_active' => true,
            ],
        );
        $doctorUser->syncRoles(['doctor']);

        $doctor = Doctor::query()->updateOrCreate(
            ['user_id' => $doctorUser->id],
            [
                'license_number' => 'MED-1001',
                'bio' => 'Médico general de demostración.',
                'is_active' => true,
            ],
        );

        $specialtyId = \App\Modules\Catalogs\Models\Specialty::query()->value('id');
        if ($specialtyId) {
            $doctor->specialties()->syncWithoutDetaching([$specialtyId]);
        }

        // Mon–Fri 08:00–17:00
        $doctor->schedules()->delete();
        foreach ([1, 2, 3, 4, 5] as $weekday) {
            $doctor->schedules()->create([
                'weekday' => $weekday,
                'start_time' => '08:00',
                'end_time' => '17:00',
                'slot_minutes' => 30,
                'is_active' => true,
            ]);
        }

        $receptionist = User::query()->updateOrCreate(
            ['email' => 'recepcion@demo.test'],
            [
                'name' => 'Recepcionista Demo',
                'password' => 'Recepcion1234',
                'is_active' => true,
            ],
        );
        $receptionist->syncRoles(['receptionist']);
    }
}
