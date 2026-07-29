<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingService
{
    public function index()
    {
        $bookings = Booking::all();

        return response()->json([
            'bookings' => $bookings,
        ]);
    }

    public function getBookingsByUser(Request $request)
    {
        $bookings = Booking::where('user_id', Auth::id())->get();

        return response()->json([
            'bookings' => $bookings,
        ]);
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'venue_id' => 'required',
            'slot_id' => 'nullable',
            'date' => 'required|date',
            'from_time' => 'required',
            'to_time' => 'required',
        ]);

        $checkSlot = $this->checkIfSlotExists($data['venue_id'], $data['from_time'], $data['to_time']);

        if ($checkSlot->original['exists']) {
            return response()->json([
                'message' => 'This venue already booked by another user please choose another time',
            ], 409);
        }

        $venue = Venue::findOrFail($data['venue_id']);

        $data['user_id'] = Auth::id();
        $data['price'] = $venue->price;

        $booking = Booking::create($data);

        return response()->json([
            'booking' => $booking,
        ]);
    }

    public function checkIfSlotExists($venue_id, $from_time, $to_time)
    {
        $exists = Booking::where('venue_id', $venue_id)
            ->where(function ($query) use ($from_time, $to_time) {
                $query->whereBetween('from_time', [$from_time, $to_time])
                    ->orWhereBetween('to_time', [$from_time, $to_time])
                    ->orWhere(function ($times) use ($from_time, $to_time) {
                        $times->where('from_time', '<=', $from_time)
                            ->where('to_time', '>=', $to_time);
                    });
            })->exists();

        return response()->json([
            'exists' => $exists,
        ]);
    }
}
