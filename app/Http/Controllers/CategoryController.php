<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = $request->user()->categories()
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        // Pre-processing: convert virtual group ID 0 to null for database compatibility
        if ($request->has('group_id') && $request->group_id == 0) {
            $request->merge(['group_id' => null]);
        }

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

        // Check for uniqueness within the specific group (considering nullsNotDistinct in migration)
        $exists = $request->user()->categories()
            ->where('name', $fields['name'])
            ->where('group_id', $fields['group_id'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'This category already exists here'], 422);
        }

        // Color inheritance logic: 
        // If no color is provided, inherit from group or use default
        if (empty($fields['color'])) {
            if ($fields['group_id']) {
                // Fetch the group to inherit its color
                $group = Group::find($fields['group_id']);
                $fields['color'] = $group ? $group->color : '#9e9e9e';
            } else {
                $fields['color'] = '#9e9e9e';
            }
        }

        $category = $request->user()->categories()->create($fields);

        return response()->json($category, 201);
    }

    public function update(Request $request, Category $category)
    {
        if ($category->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Pre-processing: convert virtual group ID 0 to null to allow moving to standalone
        if ($request->has('group_id') && $request->group_id == 0) {
            $request->merge(['group_id' => null]);
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
