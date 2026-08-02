<?php

namespace App\Services;

use App\Models\Venue;
use App\Models\VenueImage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VenueService
{
    public function index(Request $request)
    {
        $venues = Venue::all();

        return response()->json([
            'venues' => $venues,
        ]);
    }

    public function getVenueByCategory(Request $request, int $id)
    {
        $venues = Venue::where('category_id', $id)->get();

        return response()->json([
            'vanues' => $venues,
        ]);
    }

    public function getVenueByUser(Request $request, ?int $id = null)
    {
        $user = Auth::id();

        $venues = Venue::where('user_id', $user)->get();

        return response()->json([
            'venues' => $venues,
        ]);
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string',
            'address' => 'required|string',
            'use_type' => 'required|string',
            'location' => 'required|string',
            'owner_phone' => 'required|string',
            'availability' => 'required|string',
            'price' => 'required|numeric',

            'images' => 'required|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        DB::transaction(function () use ($data, $request) {

            $venue = Venue::create([
                'user_id' => Auth::id(),
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'address' => $data['address'],
                'use_type' => $data['use_type'],
                'location' => $data['location'],
                'owner_phone' => $data['owner_phone'],
                'availability' => $data['availability'],
                'price' => $data['price'],
            ]);

            /** @var array<UploadedFile> $images */
            $images = $request->file('images');

            foreach ($images as $image) {
                $path = $image->store('venues', 'public');

                $venue->images()->create([
                    'image' => $path,
                ]);
            }
        });

        return response()->json([
            'message' => 'Venue created successfully.',
        ], 201);
    }

    public function update(Request $request, Venue $venue)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string',
            'address' => 'required|string',
            'use_type' => 'required|string',
            'location' => 'required|string',
            'owner_phone' => 'required|string',
            'availability' => 'required|string',
            'price' => 'required|numeric',

            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:10240',

            'deleted_images' => 'nullable|array',
            'deleted_images.*' => 'exists:venue_images,id',
        ]);

        DB::transaction(function () use ($request, $venue, $data) {

            $venue->update([
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'address' => $data['address'],
                'use_type' => $data['use_type'],
                'location' => $data['location'],
                'owner_phone' => $data['owner_phone'],
                'availability' => $data['availability'],
                'price' => $data['price'],
            ]);

            if (! empty($data['deleted_images'])) {

                $images = $venue->images()
                    ->whereIn('id', $data['deleted_images'])
                    ->get();

                foreach ($images as $image) {
                    /** @var VenueImage $image */
                    Storage::disk('public')->delete($image->image);
                    $image->delete();
                }
            }

            if ($request->hasFile('images')) {

                /** @var array<UploadedFile> $images */
                $images = $request->file('images');

                foreach ($images as $image) {

                    $path = $image->store('venues', 'public');

                    $venue->images()->create([
                        'image' => $path,
                    ]);
                }
            }
        });

        return response()->json([
            'message' => 'Venue updated successfully.',
            'venue' => $venue->load('images'),
        ]);
    }

    public function destroy(Venue $venue)
    {
        DB::transaction(function () use ($venue) {

            foreach ($venue->images as $image) {

                Storage::disk('public')->delete($image->image);

                $image->delete();
            }

            $venue->delete();
        });

        return response()->json([
            'message' => 'Venue deleted successfully.',
        ]);
    }

    public function getAssets(Venue $venue)
    {
        $assets = $venue->images->map(function (VenueImage $image) {
            return [
                'id' => $image->id,
                'type' => 'image',
                'url' => asset("storage/{$image->image}"),
                'path' => $image->image,
            ];
        });

        return response()->json([
            'assets' => $assets,
        ]);
    }

    public function getLatestVenues()
    {
        $venues = Venue::latest()->take(6)->get();

        return response()->json([
            'venues' => $venues,
        ]);
    }
}
