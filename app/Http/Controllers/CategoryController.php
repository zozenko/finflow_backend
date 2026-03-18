<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $standalone = $user->categories()
            ->whereNull('group_id')
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        $groups = $user->groups()
            ->with(['categories' => function ($query) {
                $query->orderBy('sort_order', 'asc')
                    ->orderBy('name', 'asc');
            }])
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'standalone_categories' => $standalone,
            'groups' => $groups
        ]);
    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'name'       => 'required|string|max:255',
            'icon_key'   => 'required|string|max:255',
            'color'      => 'nullable|string|max:7',
            'group_id'   => [
                'nullable',
                Rule::exists('groups', 'id')->where(fn($q) => $q->where('user_id', $request->user()->id))
            ],
            'sort_order' => 'nullable|integer',
        ]);

        $exists = Category::where('user_id', $request->user()->id)
            ->where('name', $fields['name'])
            ->where('group_id', $fields['group_id'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'This category already exists here'], 422);
        }

        $category = $request->user()->categories()->create($fields);

        return response()->json($category, 201);
    }

    public function update(Request $request, Category $category)
    {
        if ($category->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $fields = $request->validate([
            'name'       => 'sometimes|required|string|max:255',
            'icon_key'   => 'sometimes|required|string|max:255',
            'color'      => 'sometimes|required|string|max:7',
            'sort_order' => 'sometimes|required|integer',
            'group_id'   => [
                'sometimes',
                'nullable',
                Rule::exists('groups', 'id')->where(fn($q) => $q->where('user_id', $request->user()->id))
            ],
        ]);

        $category->update($fields);

        return response()->json($category->load('group'));
    }

    public function show(Request $request, Category $category)
    {
        if ($category->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized action.');
        }

        return response()->json($category);
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
