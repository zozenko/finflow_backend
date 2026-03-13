<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        return response()->json($request->user()->categories()->orderBy('name', 'asc')->get());
    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'name'  => 'required|string|max:255',
            'icon'  => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
        ]);

        $category = Category::create([
            'name'    => $fields['name'],
            'icon'    => $fields['icon'] ?? '📁',
            'color'   => $fields['color'] ?? '#cccccc',
            'user_id' => $request->user()->id,
        ]);

        return response()->json($category, 201);
    }

    public function destroy(Request $request, Category $category)
    {
        if ($category->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $category->delete();
        return response()->json(['message' => 'Category deleted']);
    }
}
