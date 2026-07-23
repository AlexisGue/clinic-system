<?php

namespace App\Modules\Settings\Controllers;

use App\Modules\Settings\Requests\UpdateSettingsRequest;
use App\Modules\Settings\Services\SettingService;
use App\Support\Http\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends ApiController
{
    public function __construct(
        private readonly SettingService $settings,
    ) {}

    public function show(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('settings.view'), 403);

        return $this->success([
            'settings' => $this->settings->all(),
            'company' => $this->settings->company(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $settings = $this->settings->update($request->validated());

        return $this->success([
            'settings' => $settings,
            'company' => $this->settings->company(),
        ], 'Configuración guardada correctamente.');
    }
}
