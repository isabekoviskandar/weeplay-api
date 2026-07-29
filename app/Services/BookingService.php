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

        $venue = Venue::findOrFail($data['venue_id']);

        $data['user_id'] = Auth::id();
        $data['price'] = $venue->price;

        $booking = Booking::create($data);

        return response()->json([
            'booking' => $booking,
        ]);
    }
}
