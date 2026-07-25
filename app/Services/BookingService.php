<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class BookingService
{
    /**
     * Crear una reserva garantizando atomicidad, horarios laborales y evitando Race Conditions.
     */
    public function createBooking(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            $service = Service::findOrFail($data['service_id']);
            
            $startTime = Carbon::parse($data['start_time']);
            $endTime = $startTime->copy()->addMinutes($service->duration_minutes);

            // 1. Validar si cae en un día y horario laboral del personal
            $dayOfWeek = $startTime->dayOfWeek; // 0 (Domingo) a 6 (Sábado)
            $schedule = Schedule::where('staff_id', $data['staff_id'])
                ->where('day_of_week', $dayOfWeek)
                ->where('is_working_day', true)
                ->first();

            if (!$schedule) {
                throw new Exception("El personal no labora en el día seleccionado.");
            }

            $workStart = Carbon::parse($startTime->format('Y-m-d') . ' ' . $schedule->start_time);
            $workEnd   = Carbon::parse($startTime->format('Y-m-d') . ' ' . $schedule->end_time);

            if ($startTime->lt($workStart) || $endTime->gt($workEnd)) {
                throw new Exception("El horario seleccionado está fuera de la jornada laboral (" . $schedule->start_time . " - " . $schedule->end_time . ").");
            }

            // 2. Bloqueo pesimista sobre reservas existentes para prevenir solapamiento
            $hasConflict = Booking::where('staff_id', $data['staff_id'])
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) use ($startTime, $endTime) {
                    $query->whereBetween('start_time', [$startTime, $endTime])
                          ->orWhereBetween('end_time', [$startTime, $endTime])
                          ->orWhere(function ($q) use ($startTime, $endTime) {
                              $q->where('start_time', '<=', $startTime)
                                ->where('end_time', '>=', $endTime);
                          });
                })
                ->lockForUpdate()
                ->exists();

            if ($hasConflict) {
                throw new Exception("El horario seleccionado ya no se encuentra disponible.");
            }

            // 3. Crear la reserva de forma atómica
            return Booking::create([
                'business_id' => $data['business_id'],
                'service_id'  => $data['service_id'],
                'staff_id'    => $data['staff_id'],
                'customer_id' => $data['customer_id'],
                'start_time'  => $startTime,
                'end_time'    => $endTime,
                'total_price' => $service->price,
                'status'      => 'pending',
            ]);
        });
    }
}