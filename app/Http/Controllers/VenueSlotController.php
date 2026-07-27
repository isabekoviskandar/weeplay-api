<?php

namespace App\Http\Controllers;

use App\Services\SlotService;
use Illuminate\Http\Request;

class VenueSlotController extends Controller
{
    protected $service;

    public function __construct(SlotService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return $this->service->index();
    }

    public function getSlotsByVenue($request, $id)
    {
        return $this->service->getSlotsByVenue($request, $id);
    }

    public function getSlotsByCategory($request, int $id)
    {
        return $this->service->getSlotsByCategory($request, $id);
    }

    public function getSlotsByUser(Request $request)
    {
        return $this->service->getSlotsByUser($request);
    }

    public function create(Request $request)
    {
        return $this->service->create($request);
    }

    public function update() {}

    public function showSlot(int $id) {}
}
