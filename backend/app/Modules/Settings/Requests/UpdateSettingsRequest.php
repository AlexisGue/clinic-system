<?php

namespace App\Modules\Settings\Requests;

use App\Modules\Settings\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            Setting::KEY_CLINIC_NAME => ['sometimes', 'nullable', 'string', 'max:255'],
            Setting::KEY_TAX_ID => ['sometimes', 'nullable', 'string', 'max:50'],
            Setting::KEY_ADDRESS => ['sometimes', 'nullable', 'string', 'max:500'],
            Setting::KEY_PHONE => ['sometimes', 'nullable', 'string', 'max:50'],
            Setting::KEY_EMAIL => ['sometimes', 'nullable', 'email', 'max:255'],
            Setting::KEY_TIMEZONE => ['sometimes', 'nullable', 'string', 'max:100'],
            Setting::KEY_CURRENCY => ['sometimes', 'nullable', 'string', 'size:3', Rule::in(['USD', 'MXN'])],
            Setting::KEY_BUSINESS_HOURS_JSON => ['sometimes', 'nullable'],
            Setting::KEY_APPOINTMENT_DEFAULT_DURATION => ['sometimes', 'nullable', 'integer', 'min:5', 'max:240'],
            Setting::KEY_TICKET_FOOTER => ['sometimes', 'nullable', 'string', 'max:500'],
            Setting::KEY_REQUIRE_PAYMENT_TO_COMPLETE => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
