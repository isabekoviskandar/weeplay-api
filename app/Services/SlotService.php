<?php

namespace App\Services;

use App\Models\VenueSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SlotService
{
    public function index()
    {
        $slots = VenueSlot::all();

        return response()->json([
            'slots' => $slots,
        ]);
    }

    public function getSlotsByVenue(Request $request, $id)
    {
        $slots = VenueSlot::where('venue_id', $id)->get();

        return response()->json([
            'slots' => $slots,
        ]);
    }

    public function getSlotsByCategory(Request $request, $id)
    {
        $slots = VenueSlot::whereHas('venue', function ($query) use ($id) {
            $query->where('category_id', $id);
        })->get();

        return response()->json([
            'slots' => $slots,
        ]);
    }

    public function getSlotsByUser(Request $request)
    {
        $slots = VenueSlot::where('user_id', Auth::id())->get();

        return response()->json([
            'slots' => $slots,
        ]);
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'venue_id' => 'required|integer',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'price' => 'required',
        ]);

        $data['user_id'] = 1;

        $slot = VenueSlot::create($data);

        return response()->json([
            'slot' => $slot,
        ]);
    }

    public function showSlot(int $id) {}
}
