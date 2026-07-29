<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserService
{
    public function me()
    {
        $user = Auth::user();

        return response()->json([
            'user' => $user,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = User::query()->findOrFail(Auth::id());

        $data = $request->validate([
            'username' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'bio' => ['sometimes', 'nullable', 'string'],
            'password' => ['sometimes', 'string', 'min:8'],
            'image' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'cover_image' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        foreach (['image', 'cover_image'] as $field) {
            if ($request->hasFile($field)) {
                if ($user->{$field}) {
                    Storage::disk('public')->delete($user->{$field});
                }

                $data[$field] = $request->file($field)->store('users', 'public');
            } elseif (array_key_exists($field, $data) && $data[$field] === null && $user->{$field}) {
                Storage::disk('public')->delete($user->{$field});
            }
        }

        $user->update($data);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user->fresh(),
        ]);
    }

    public function getUserVeneus(Request $request)
    {
        $venues = Venue::where('user_id', Auth::id())->get();

        return response()->json([
            'venues' => $venues,
        ]);
    }

    public function getUserBookings(Request $request)
    {
        $bookings = Booking::where('user_id', Auth::id())->get();

        return response()->json([
            'bookings' => $bookings,
        ]);
    }

    public function getUserSlots(Request $request)
    {
        $slots = VenueSlot::where('user_id', Auth::id())->get();

        return response()->json([
            'slots' => $slots,
        ]);
    }
}
