<?php

namespace App\Http\Controllers;

use App\Services\BookingService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    protected $service;

    public function __construct(BookingService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return $this->service->index();
    }

    public function getBookingByUser(Request $request)
    {
        return $this->service->getBookingsByUser($request);
    }

    public function create(Request $request)
    {
        return $this->service->create($request);
    }
}
