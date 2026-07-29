<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    public function me()
    {
        return $this->service->me();
    }

    public function updateProfile(Request $request)
    {
        return $this->service->updateProfile($request);
    }

    public function getUserVenues(Request $request)
    {
        return $this->service->getUserVeneus($request);
    }

    public function getUserBookings(Request $request)
    {
        return $this->service->getUserBookings($request);
    }

    public function getUserSlots(Request $request)
    {
        return $this->service->getUserSlots($request);
    }
}
