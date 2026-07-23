<?php

namespace App\Modules\Payments\Controllers;

use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\Payments\Resources\PaymentMethodResource;
use App\Support\Http\ApiController;
use Illuminate\Http\JsonResponse;

class PaymentMethodController extends ApiController
{
    public function index(): JsonResponse
    {
        abort_unless(auth()->user()?->can('payments.view') || auth()->user()?->can('payments.create'), 403);

        $methods = PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return $this->success(PaymentMethodResource::collection($methods));
    }
}
