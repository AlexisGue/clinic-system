<?php

namespace Database\Seeders;

use App\Modules\Catalogs\Models\Medicine;
use App\Modules\Catalogs\Models\Specialty;
use App\Modules\Payments\Models\PaymentMethod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ClinicCatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'Medicina General',
            'Pediatría',
            'Ginecología',
            'Cardiología',
            'Dermatología',
        ] as $name) {
            Specialty::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true],
            );
        }

        foreach ([
            ['name' => 'Paracetamol', 'presentation' => 'Tableta', 'concentration' => '500 mg'],
            ['name' => 'Ibuprofeno', 'presentation' => 'Tableta', 'concentration' => '400 mg'],
            ['name' => 'Amoxicilina', 'presentation' => 'Cápsula', 'concentration' => '500 mg'],
            ['name' => 'Loratadina', 'presentation' => 'Tableta', 'concentration' => '10 mg'],
            ['name' => 'Omeprazol', 'presentation' => 'Cápsula', 'concentration' => '20 mg'],
        ] as $medicine) {
            Medicine::query()->updateOrCreate(
                ['name' => $medicine['name']],
                [
                    'presentation' => $medicine['presentation'],
                    'concentration' => $medicine['concentration'],
                    'is_active' => true,
                ],
            );
        }

        foreach ([
            ['code' => 'cash', 'name' => 'Efectivo'],
            ['code' => 'card', 'name' => 'Tarjeta'],
            ['code' => 'transfer', 'name' => 'Transferencia'],
        ] as $method) {
            PaymentMethod::query()->updateOrCreate(
                ['code' => $method['code']],
                ['name' => $method['name'], 'is_active' => true],
            );
        }
    }
}
