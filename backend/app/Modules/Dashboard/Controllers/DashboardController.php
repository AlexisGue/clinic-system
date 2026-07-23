<?php

namespace App\Modules\Dashboard\Controllers;

use App\Modules\Dashboard\Services\DashboardService;
use App\Support\Http\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends ApiController
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    public function summary(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('dashboard.view'), 403);

        return $this->success(
            $this->dashboard->summary(
                $request->query('from'),
                $request->query('to'),
            )
        );
    }
}
