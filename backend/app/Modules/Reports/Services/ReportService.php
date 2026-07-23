<?php

namespace App\Modules\Reports\Services;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Consultations\Models\Consultation;
use App\Modules\Doctors\Models\Doctor;
use App\Modules\Patients\Models\Patient;
use App\Modules\Payments\Models\Payment;
use App\Modules\Settings\Services\SettingService;
use App\Support\Auth\ClinicActor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    public function __construct(
        private readonly SettingService $settings,
    ) {}

    /**
     * @return array{from: string, to: string}
     */
    public function resolvePeriod(?string $from, ?string $to): array
    {
        return [
            'from' => $from ? Carbon::parse($from)->toDateString() : now()->startOfMonth()->toDateString(),
            'to' => $to ? Carbon::parse($to)->toDateString() : now()->toDateString(),
        ];
    }

    public function patients(?string $from = null, ?string $to = null): array
    {
        $period = $this->resolvePeriod($from, $to);

        $rows = Patient::query()
            ->whereDate('created_at', '>=', $period['from'])
            ->whereDate('created_at', '<=', $period['to'])
            ->orderBy('created_at')
            ->get()
            ->map(fn (Patient $p) => [
                'document_number' => $p->document_number,
                'full_name' => $p->full_name,
                'phone' => $p->phone ?? '—',
                'email' => $p->email ?? '—',
                'created_at' => $p->created_at?->format('Y-m-d'),
                'is_active' => $p->is_active ? 'Sí' : 'No',
            ])->all();

        return [
            'period' => $period,
            'summary' => ['count' => count($rows)],
            'rows' => $rows,
        ];
    }

    public function appointments(?string $from = null, ?string $to = null): array
    {
        $period = $this->resolvePeriod($from, $to);

        $rows = Appointment::query()
            ->with(['patient:id,first_name,last_name', 'doctor.user:id,name'])
            ->whereDate('starts_at', '>=', $period['from'])
            ->whereDate('starts_at', '<=', $period['to'])
            ->when(auth()->user(), fn ($q) => ClinicActor::scopeOwnDoctor($q, auth()->user()))
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Appointment $a) => [
                'folio' => $a->folio,
                'starts_at' => $a->starts_at?->format('Y-m-d H:i'),
                'patient' => trim(($a->patient?->first_name ?? '').' '.($a->patient?->last_name ?? '')),
                'doctor' => $a->doctor?->user?->name ?? '—',
                'status' => $a->status,
            ])->all();

        return [
            'period' => $period,
            'summary' => ['count' => count($rows)],
            'rows' => $rows,
        ];
    }

    public function consultations(?string $from = null, ?string $to = null): array
    {
        $period = $this->resolvePeriod($from, $to);
        $includeDiagnosis = (bool) auth()->user()?->can('consultations.view');

        $rows = Consultation::query()
            ->with(['patient:id,first_name,last_name', 'doctor.user:id,name'])
            ->whereDate('attended_at', '>=', $period['from'])
            ->whereDate('attended_at', '<=', $period['to'])
            ->when(auth()->user(), fn ($q) => ClinicActor::scopeOwnDoctor($q, auth()->user()))
            ->orderBy('attended_at')
            ->get()
            ->map(function (Consultation $c) use ($includeDiagnosis) {
                $row = [
                    'folio' => $c->folio,
                    'attended_at' => $c->attended_at?->format('Y-m-d H:i'),
                    'patient' => trim(($c->patient?->first_name ?? '').' '.($c->patient?->last_name ?? '')),
                    'doctor' => $c->doctor?->user?->name ?? '—',
                    'status' => $c->status,
                ];

                if ($includeDiagnosis) {
                    $row['diagnosis'] = $c->diagnosis ?? '—';
                }

                return $row;
            })->all();

        return [
            'period' => $period,
            'summary' => ['count' => count($rows)],
            'rows' => $rows,
        ];
    }

    public function doctors(?string $from = null, ?string $to = null): array
    {
        $period = $this->resolvePeriod($from, $to);

        $rows = Doctor::query()
            ->with(['user:id,name,email', 'specialties:id,name'])
            ->orderBy('id')
            ->get()
            ->map(function (Doctor $d) use ($period) {
                $appointments = Appointment::query()
                    ->where('doctor_id', $d->id)
                    ->whereDate('starts_at', '>=', $period['from'])
                    ->whereDate('starts_at', '<=', $period['to'])
                    ->where('status', '!=', Appointment::STATUS_CANCELLED)
                    ->count();

                return [
                    'name' => $d->user?->name ?? '—',
                    'email' => $d->user?->email ?? '—',
                    'license_number' => $d->license_number ?? '—',
                    'specialties' => $d->specialties->pluck('name')->implode(', ') ?: '—',
                    'appointments' => $appointments,
                    'is_active' => $d->is_active ? 'Sí' : 'No',
                ];
            })->all();

        return [
            'period' => $period,
            'summary' => ['count' => count($rows)],
            'rows' => $rows,
        ];
    }

    public function payments(?string $from = null, ?string $to = null): array
    {
        $period = $this->resolvePeriod($from, $to);

        $rows = Payment::query()
            ->with(['paymentMethod:id,name', 'recorder:id,name'])
            ->whereDate('paid_at', '>=', $period['from'])
            ->whereDate('paid_at', '<=', $period['to'])
            ->orderBy('paid_at')
            ->get()
            ->map(fn (Payment $p) => [
                'paid_at' => $p->paid_at?->format('Y-m-d H:i'),
                'amount' => (float) $p->amount,
                'currency' => $p->currency,
                'method' => $p->paymentMethod?->name ?? '—',
                'reference' => $p->reference ?? '—',
                'recorded_by' => $p->recorder?->name ?? '—',
            ])->all();

        return [
            'period' => $period,
            'summary' => [
                'count' => count($rows),
                'total' => round(array_sum(array_column($rows, 'amount')), 2),
            ],
            'rows' => $rows,
        ];
    }

    public function exportPdf(string $type, array $report): \Barryvdh\DomPDF\PDF
    {
        $titles = [
            'patients' => 'Reporte de pacientes',
            'appointments' => 'Reporte de citas',
            'consultations' => 'Reporte de consultas',
            'doctors' => 'Reporte de médicos',
            'payments' => 'Reporte de pagos',
        ];

        return Pdf::loadView('reports.clinic', [
            'title' => $titles[$type] ?? 'Reporte',
            'type' => $type,
            'report' => $report,
            'company' => $this->settings->company(),
            'generatedAt' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape');
    }

    /**
     * @param  list<string>  $headers
     * @param  Collection<int, array<string, mixed>>|list<array<string, mixed>>  $rows
     */
    public function exportCsv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);

            foreach ($rows as $row) {
                fputcsv($out, array_values($row));
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
