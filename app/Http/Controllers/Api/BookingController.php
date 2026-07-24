<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Exception;

class BookingController extends Controller
{
    protected $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'service_id'  => 'required|exists:services,id',
            'staff_id'    => 'required|exists:staff,id',
            'customer_id' => 'required|exists:users,id',
            'start_time'  => 'required|date_format:Y-m-d H:i:s|after:now',
        ]);

        try {
            $booking = $this->bookingService->createBooking($validated);

            return response()->json([
                'message' => '¡Reserva creada exitosamente!',
                'data'    => $booking
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 422);
        }
    }
}
