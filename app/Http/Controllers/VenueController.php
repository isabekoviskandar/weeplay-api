<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use App\Services\VenueService;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    protected $service;

    public function __construct(VenueService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        return $this->service->index($request);
    }

    public function getVenueByCategory(Request $request, int $id)
    {
        return $this->service->getVenueByCategory($request, $id);
    }

    public function getVenueByUser(Request $request, ?int $id = null)
    {
        return $this->service->getVenueByUser($request, $id);
    }

    public function create(Request $request)
    {
        return $this->service->create($request);
    }

    public function update(Request $request, Venue $venue)
    {
        return $this->service->update($request, $venue);
    }

    public function destroy(Venue $venue)
    {
        return $this->service->destroy($venue);
    }
}
