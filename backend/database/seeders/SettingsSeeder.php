<?php

namespace Database\Seeders;

use App\Modules\Settings\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            Setting::KEY_CLINIC_NAME => 'Clinic System Demo',
            Setting::KEY_TAX_ID => '12-3456789',
            Setting::KEY_ADDRESS => '100 Health Ave, Miami, FL 33101',
            Setting::KEY_PHONE => '+1 305-555-0200',
            Setting::KEY_EMAIL => 'contact@clinic-demo.test',
            Setting::KEY_TIMEZONE => 'America/Mexico_City',
            Setting::KEY_CURRENCY => 'USD',
            Setting::KEY_BUSINESS_HOURS_JSON => json_encode([
                'mon' => ['08:00', '18:00'],
                'tue' => ['08:00', '18:00'],
                'wed' => ['08:00', '18:00'],
                'thu' => ['08:00', '18:00'],
                'fri' => ['08:00', '18:00'],
                'sat' => ['09:00', '13:00'],
                'sun' => null,
            ]),
            Setting::KEY_APPOINTMENT_DEFAULT_DURATION => '30',
            Setting::KEY_TICKET_FOOTER => 'Gracias por confiar en nuestra clínica.',
            Setting::KEY_REQUIRE_PAYMENT_TO_COMPLETE => '0',
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }

        Cache::forget('app.settings.map');
    }
}
