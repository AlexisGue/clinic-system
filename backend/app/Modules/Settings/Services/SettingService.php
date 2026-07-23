<?php

namespace App\Modules\Settings\Services;

use App\Modules\Settings\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SettingService
{
    private const CACHE_KEY = 'app.settings.map';

    /**
     * @return array<string, string|null>
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            $stored = Setting::query()->pluck('value', 'key')->all();
            $map = [];

            foreach (Setting::ALLOWED_KEYS as $key) {
                $map[$key] = array_key_exists($key, $stored) ? $stored[$key] : null;
            }

            return $map;
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return $all[$key] ?? $default;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string|null>
     */
    public function update(array $data): array
    {
        return DB::transaction(function () use ($data) {
            foreach (Setting::ALLOWED_KEYS as $key) {
                if (! array_key_exists($key, $data)) {
                    continue;
                }

                $value = $data[$key];
                if ($value === '') {
                    $value = null;
                }

                if ($key === Setting::KEY_CURRENCY && $value !== null) {
                    $value = strtoupper((string) $value);
                }

                if ($key === Setting::KEY_REQUIRE_PAYMENT_TO_COMPLETE && $value !== null) {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
                }

                if ($key === Setting::KEY_APPOINTMENT_DEFAULT_DURATION && $value !== null) {
                    $value = (string) (int) $value;
                }

                if ($key === Setting::KEY_BUSINESS_HOURS_JSON && is_array($value)) {
                    $value = json_encode($value);
                }

                Setting::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => $value !== null ? (string) $value : null],
                );
            }

            Cache::forget(self::CACHE_KEY);

            return $this->all();
        });
    }

    /**
     * Company block for PDF headers / tickets.
     *
     * @return array{business_name: string, rfc: ?string, address: ?string, phone: ?string, email: ?string, ticket_footer: ?string, currency: string}
     */
    public function company(): array
    {
        $all = $this->all();

        return [
            'business_name' => $all[Setting::KEY_CLINIC_NAME] ?: config('app.name'),
            'rfc' => $all[Setting::KEY_TAX_ID],
            'address' => $all[Setting::KEY_ADDRESS],
            'phone' => $all[Setting::KEY_PHONE],
            'email' => $all[Setting::KEY_EMAIL],
            'ticket_footer' => $all[Setting::KEY_TICKET_FOOTER],
            'currency' => $all[Setting::KEY_CURRENCY] ?: 'USD',
        ];
    }
}
