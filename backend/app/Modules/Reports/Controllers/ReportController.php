<?php

namespace App\Modules\Reports\Controllers;

use App\Modules\Reports\Services\ReportService;
use App\Support\Http\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends ApiController
{
    public function __construct(
        private readonly ReportService $reports,
    ) {}

    public function patients(Request $request): JsonResponse|Response
    {
        return $this->handle($request, 'patients', $this->reports->patients($request->query('from'), $request->query('to')), [
            'Documento', 'Nombre', 'Teléfono', 'Email', 'Alta', 'Activo',
        ], fn (array $r) => array_values($r), 'reporte-pacientes');
    }

    public function appointments(Request $request): JsonResponse|Response
    {
        return $this->handle($request, 'appointments', $this->reports->appointments($request->query('from'), $request->query('to')), [
            'Folio', 'Fecha', 'Paciente', 'Médico', 'Estado',
        ], fn (array $r) => array_values($r), 'reporte-citas');
    }

    public function consultations(Request $request): JsonResponse|Response
    {
        abort_unless($request->user()?->can('consultations.view'), 403);

        return $this->handle($request, 'consultations', $this->reports->consultations($request->query('from'), $request->query('to')), [
            'Folio', 'Fecha', 'Paciente', 'Médico', 'Estado', 'Diagnóstico',
        ], fn (array $r) => array_values($r), 'reporte-consultas');
    }

    public function doctors(Request $request): JsonResponse|Response
    {
        return $this->handle($request, 'doctors', $this->reports->doctors($request->query('from'), $request->query('to')), [
            'Nombre', 'Email', 'Cédula', 'Especialidades', 'Citas', 'Activo',
        ], fn (array $r) => array_values($r), 'reporte-medicos');
    }

    public function payments(Request $request): JsonResponse|Response
    {
        return $this->handle($request, 'payments', $this->reports->payments($request->query('from'), $request->query('to')), [
            'Fecha', 'Monto', 'Moneda', 'Método', 'Referencia', 'Registró',
        ], fn (array $r) => array_values($r), 'reporte-pagos');
    }

    /**
     * @param  list<string>  $csvHeaders
     * @param  callable(array<string, mixed>): list<mixed>  $csvMap
     */
    private function handle(
        Request $request,
        string $type,
        array $report,
        array $csvHeaders,
        callable $csvMap,
        string $basename,
    ): JsonResponse|Response {
        abort_unless($request->user()?->can('reports.view'), 403);

        $format = strtolower((string) $request->query('format', 'json'));

        if ($format === 'json') {
            return $this->success($report);
        }

        abort_unless($request->user()?->can('reports.export'), 403);

        if ($format === 'pdf') {
            return $this->reports->exportPdf($type, $report)->download($basename.'-'.now()->format('Ymd').'.pdf');
        }

        if (in_array($format, ['csv', 'excel', 'xlsx'], true)) {
            $rows = array_map($csvMap, $report['rows']);

            return $this->reports->exportCsv(
                $basename.'-'.now()->format('Ymd').'.csv',
                $csvHeaders,
                $rows
            );
        }

        abort(422, 'Formato no soportado. Usa json, pdf o csv.');
    }
}
