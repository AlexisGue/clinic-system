<?php

namespace App\Modules\Dashboard\Services;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Consultations\Models\Consultation;
use App\Modules\Patients\Models\Patient;
use App\Modules\Payments\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(?string $from = null, ?string $to = null): array
    {
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : now()->startOfMonth();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $patientsCount = Patient::query()->where('is_active', true)->count();

        $appointmentsToday = Appointment::query()
            ->whereNotIn('status', [Appointment::STATUS_CANCELLED])
            ->whereBetween('starts_at', [$todayStart, $todayEnd])
            ->count();

        $upcomingAppointments = Appointment::query()
            ->with(['patient:id,first_name,last_name', 'doctor.user:id,name'])
            ->whereNotIn('status', [Appointment::STATUS_CANCELLED, Appointment::STATUS_COMPLETED, Appointment::STATUS_NO_SHOW])
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(8)
            ->get()
            ->map(fn (Appointment $a) => [
                'id' => $a->id,
                'folio' => $a->folio,
                'starts_at' => $a->starts_at?->toIso8601String(),
                'status' => $a->status,
                'patient' => trim(($a->patient?->first_name ?? '').' '.($a->patient?->last_name ?? '')),
                'doctor' => $a->doctor?->user?->name,
            ])
            ->all();

        $consultationsPeriod = Consultation::query()
            ->whereBetween('attended_at', [$fromDate, $toDate])
            ->count();

        $paymentsPeriodTotal = (float) Payment::query()
            ->whereBetween('paid_at', [$fromDate, $toDate])
            ->sum('amount');

        $appointmentsLast7 = Appointment::query()
            ->whereNotIn('status', [Appointment::STATUS_CANCELLED])
            ->where('starts_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(starts_at) as day, COUNT(*) as count')
            ->groupBy(DB::raw('DATE(starts_at)'))
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $chart = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $row = $appointmentsLast7->get($day);
            $chart[] = [
                'date' => $day,
                'label' => Carbon::parse($day)->format('d/m'),
                'count' => (int) ($row->count ?? 0),
            ];
        }

        return [
            'period' => [
                'from' => $fromDate->toDateString(),
                'to' => $toDate->toDateString(),
            ],
            'kpis' => [
                'patients_count' => $patientsCount,
                'appointments_today' => $appointmentsToday,
                'consultations_period' => $consultationsPeriod,
                'payments_period_total' => round($paymentsPeriodTotal, 2),
            ],
            'upcoming_appointments' => $upcomingAppointments,
            'appointments_chart' => $chart,
        ];
    }
}
