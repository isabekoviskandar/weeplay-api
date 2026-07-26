<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryService
{
    public function index()
    {
        $categories = Category::all();

        return response()->json([
            'categories' => $categories,
        ]);
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|json',
            'image' => 'required|image|mimes:png,jpg,jpeg,svg|max:10240',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('images', 'public');
        }

        $category = Category::create($data);

        return response()->json([
            'category' => $category,
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $category = Category::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,svg|max:10240',
        ]);

        if ($request->hasFile('image')) {

            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }

            $data['image'] = $request->file('image')->store('images', 'public');
        }

        $category->update($data);

        return response()->json([
            'message' => 'Category updated successfully.',
            'category' => $category,
        ], 200);
    }

    public function destroy(int $id)
    {
        $category = Category::findOrFail($id);

        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully.',
        ], 200);
    }
}
