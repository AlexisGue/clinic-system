<?php

namespace App\Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public const KEY_CLINIC_NAME = 'clinic_name';

    public const KEY_TAX_ID = 'tax_id';

    public const KEY_ADDRESS = 'address';

    public const KEY_PHONE = 'phone';

    public const KEY_EMAIL = 'email';

    public const KEY_TIMEZONE = 'timezone';

    public const KEY_CURRENCY = 'currency';

    public const KEY_BUSINESS_HOURS_JSON = 'business_hours_json';

    public const KEY_APPOINTMENT_DEFAULT_DURATION = 'appointment_default_duration';

    public const KEY_TICKET_FOOTER = 'ticket_footer';

    public const KEY_REQUIRE_PAYMENT_TO_COMPLETE = 'require_payment_to_complete';

    /** @var list<string> */
    public const ALLOWED_KEYS = [
        self::KEY_CLINIC_NAME,
        self::KEY_TAX_ID,
        self::KEY_ADDRESS,
        self::KEY_PHONE,
        self::KEY_EMAIL,
        self::KEY_TIMEZONE,
        self::KEY_CURRENCY,
        self::KEY_BUSINESS_HOURS_JSON,
        self::KEY_APPOINTMENT_DEFAULT_DURATION,
        self::KEY_TICKET_FOOTER,
        self::KEY_REQUIRE_PAYMENT_TO_COMPLETE,
    ];
}
