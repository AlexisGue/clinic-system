<?php

namespace App\Modules\Audit\Controllers;

use App\Modules\Audit\Resources\AuditResource;
use App\Modules\Audit\Services\AuditService;
use App\Support\Http\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;

class AuditController extends ApiController
{
    public function __construct(
        private readonly AuditService $audits,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Audit::class);

        $paginator = $this->audits->list($request->only([
            'user_id',
            'event',
            'auditable_type',
            'per_page',
        ]));

        return $this->success([
            'items' => AuditResource::collection($paginator->getCollection()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
