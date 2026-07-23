<?php

namespace App\Modules\Appointments\Services;

use App\Modules\Appointments\Exceptions\AppointmentDomainException;
use App\Modules\Appointments\Models\Appointment;
use App\Modules\Appointments\Repositories\AppointmentRepository;
use App\Modules\Doctors\Models\Doctor;
use App\Modules\Doctors\Models\DoctorSchedule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    public function __construct(
        private readonly AppointmentRepository $appointments,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->appointments->paginate($filters);
    }

    public function calendar(array $filters = []): Collection
    {
        return $this->appointments->calendar($filters);
    }

    public function find(int $id): Appointment
    {
        return $this->appointments->findOrFail($id);
    }

    public function create(array $data): Appointment
    {
        $startsAt = Carbon::parse($data['starts_at']);
        $endsAt = Carbon::parse($data['ends_at']);

        return DB::transaction(function () use ($data, $startsAt, $endsAt) {
            $this->assertWithinSchedule((int) $data['doctor_id'], $startsAt, $endsAt);
            $this->assertNoOverlap((int) $data['doctor_id'], $startsAt, $endsAt);

            $appointment = Appointment::query()->create([
                'folio' => 'TMP',
                'patient_id' => $data['patient_id'],
                'doctor_id' => $data['doctor_id'],
                'specialty_id' => $data['specialty_id'] ?? null,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => Appointment::STATUS_SCHEDULED,
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $appointment->forceFill([
                'folio' => 'A-'.str_pad((string) $appointment->id, 6, '0', STR_PAD_LEFT),
            ])->save();

            return $this->appointments->findOrFail($appointment->id);
        });
    }

    public function reschedule(Appointment $appointment, array $data): Appointment
    {
        if ($appointment->isCancelled() || $appointment->status === Appointment::STATUS_COMPLETED) {
            throw AppointmentDomainException::notCancellable();
        }

        $startsAt = Carbon::parse($data['starts_at']);
        $endsAt = Carbon::parse($data['ends_at']);

        return DB::transaction(function () use ($appointment, $startsAt, $endsAt) {
            $this->assertWithinSchedule((int) $appointment->doctor_id, $startsAt, $endsAt);
            $this->assertNoOverlap((int) $appointment->doctor_id, $startsAt, $endsAt, $appointment->id);

            $appointment->update([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            return $this->appointments->findOrFail($appointment->id);
        });
    }

    public function cancel(Appointment $appointment, string $reason): Appointment
    {
        if (in_array($appointment->status, [Appointment::STATUS_CANCELLED, Appointment::STATUS_COMPLETED], true)) {
            throw AppointmentDomainException::notCancellable();
        }

        $appointment->update([
            'status' => Appointment::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        return $this->appointments->findOrFail($appointment->id);
    }

    public function updateStatus(Appointment $appointment, string $status): Appointment
    {
        $allowed = [
            Appointment::STATUS_CONFIRMED,
            Appointment::STATUS_IN_PROGRESS,
            Appointment::STATUS_NO_SHOW,
            Appointment::STATUS_COMPLETED,
        ];

        if (! in_array($status, $allowed, true)) {
            throw AppointmentDomainException::invalidStatus($status);
        }

        if ($appointment->isCancelled()) {
            throw AppointmentDomainException::notCancellable();
        }

        $appointment->update(['status' => $status]);

        return $this->appointments->findOrFail($appointment->id);
    }

    private function assertNoOverlap(int $doctorId, Carbon $startsAt, Carbon $endsAt, ?int $ignoreId = null): void
    {
        if ($this->appointments->hasOverlap($doctorId, $startsAt, $endsAt, $ignoreId)) {
            throw AppointmentDomainException::overlap();
        }
    }

    private function assertWithinSchedule(int $doctorId, Carbon $startsAt, Carbon $endsAt): void
    {
        Doctor::query()->whereKey($doctorId)->where('is_active', true)->firstOrFail();

        $weekday = (int) $startsAt->dayOfWeek; // 0=Sunday … 6=Saturday

        /** @var DoctorSchedule|null $schedule */
        $schedule = DoctorSchedule::query()
            ->where('doctor_id', $doctorId)
            ->where('weekday', $weekday)
            ->where('is_active', true)
            ->get()
            ->first(function (DoctorSchedule $s) use ($startsAt, $endsAt) {
                $day = $startsAt->toDateString();
                $slotStart = Carbon::parse($day.' '.$s->start_time);
                $slotEnd = Carbon::parse($day.' '.$s->end_time);

                return $startsAt->gte($slotStart) && $endsAt->lte($slotEnd);
            });

        if (! $schedule) {
            throw AppointmentDomainException::outsideSchedule();
        }
    }
}
