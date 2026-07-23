<?php

namespace App\Support\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Standard JSON envelope for every API response.
 *
 * Success: { "message": string|null, "data": mixed }
 * Error:   { "message": string, "code": string, "errors": object }
 */
trait ApiResponses
{
    protected function success(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * @param  LengthAwarePaginator  $paginator
     */
    protected function paginated(mixed $paginator, mixed $resourceCollection): JsonResponse
    {
        return $this->success([
            'items' => $resourceCollection,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    protected function created(mixed $data = null, ?string $message = null): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    protected function error(string $message, int $status, string $code = 'ERROR', array $errors = []): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'code' => $code,
            'errors' => (object) $errors,
        ], $status);
    }
}
