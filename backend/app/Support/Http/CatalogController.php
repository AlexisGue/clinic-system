<?php

namespace App\Support\Http;

use App\Support\Services\CatalogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Reusable CRUD controller for catalog resources.
 * Children wire: service, model class, resource class, permission checks via policy.
 */
abstract class CatalogController extends ApiController
{
    abstract protected function service(): CatalogService;

    /** @return class-string<Model> */
    abstract protected function modelClass(): string;

    /** @return class-string<JsonResource> */
    abstract protected function resourceClass(): string;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', $this->modelClass());

        $paginator = $this->service()->list($request->only(['search', 'is_active', 'per_page']));
        $resource = $this->resourceClass();

        return $this->paginated($paginator, $resource::collection($paginator->getCollection()));
    }

    public function options(): JsonResponse
    {
        $this->authorize('viewAny', $this->modelClass());

        $resource = $this->resourceClass();

        return $this->success($resource::collection($this->service()->options()));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', $this->modelClass());

        $model = $this->service()->create($this->validatedStore($request));
        $resource = $this->resourceClass();

        return $this->created(new $resource($model), 'Registro creado correctamente.');
    }

    public function show(int $id): JsonResponse
    {
        $model = $this->service()->find($id);
        $this->authorize('view', $model);

        $resource = $this->resourceClass();

        return $this->success(new $resource($model));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $model = $this->service()->find($id);
        $this->authorize('update', $model);

        $model = $this->service()->update($model, $this->validatedUpdate($request, $model));
        $resource = $this->resourceClass();

        return $this->success(new $resource($model), 'Registro actualizado correctamente.');
    }

    public function destroy(int $id): JsonResponse
    {
        $model = $this->service()->find($id);
        $this->authorize('delete', $model);

        $this->service()->delete($model);

        return $this->success(null, 'Registro eliminado correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    abstract protected function validatedStore(Request $request): array;

    /**
     * @return array<string, mixed>
     */
    abstract protected function validatedUpdate(Request $request, Model $model): array;
}
