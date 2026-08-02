<?php

namespace App\Http\Controllers;

use App\Services\CategoryService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected $service;

    public function __construct(CategoryService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return $this->service->index();
    }

    public function create(Request $request)
    {
        return $this->service->create($request);
    }

    public function update(Request $request, int $id)
    {
        return $this->service->update($request, $id);
    }

    public function destroy(int $id)
    {
        return $this->service->destroy($id);
    }

    public function getLatestCategories()
    {
        return $this->service->getLatestCategories();
    }
}
