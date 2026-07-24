<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class BookingService
{
    /**
     * Crear una reserva garantizando atomicidad y evitando Race Conditions.
     */
    public function createBooking(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            $service = Service::findOrFail($data['service_id']);
            
            // Calculamos el end_time sumando la duración del servicio
            $startTime = Carbon::parse($data['start_time']);
            $endTime = $startTime->copy()->addMinutes($service->duration_minutes);

            // 1. Bloqueo pesimista sobre las reservas existentes del personal
            // Evita que otra solicitud simultánea lea los mismos huecos libres
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
                ->lockForUpdate() // <--- ¡AQUÍ PREVENIMOS LA CONCURRENCIA!
                ->exists();

            if ($hasConflict) {
                throw new Exception("El horario seleccionado ya no se encuentra disponible.");
            }

            // 2. Crear la reserva de forma atómica
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