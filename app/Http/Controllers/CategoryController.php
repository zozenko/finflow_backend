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
        $fields = $request->validate([
            'name'       => 'required|string|max:255',
            'icon_key'   => 'required|string|max:255',
            'color'      => 'nullable|string|max:7',
            'group_id'   => [
                'required',
                Rule::exists('groups', 'id')->where(fn($q) => $q->where('user_id', $request->user()->id))
            ],
            'sort_order' => 'nullable|integer',
        ]);

        $exists = $request->user()->categories()
            ->where('name', $fields['name'])
            ->where('group_id', $fields['group_id'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'This category already exists here'], 422);
        }

        if (empty($fields['color'])) {
            $group = Group::find($fields['group_id']);
            $fields['color'] = $group->color;
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
                'required',
                Rule::exists('groups', 'id')->where(fn($q) => $q->where('user_id', $request->user()->id))
            ],
        ]);

        if (array_key_exists('group_id', $fields) && $fields['group_id'] !== $category->group_id) {
            $category->transactions()->update(['group_id' => $fields['group_id']]);
        }

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

        $fields = $request->validate([
            'reassign_to_category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn($q) => $q->where('user_id', $request->user()->id)),
            ],
        ]);

        if (!empty($fields['reassign_to_category_id'])) {
            $target = Category::find($fields['reassign_to_category_id']);
            $category->transactions()->update([
                'category_id' => $target->id,
                'group_id'    => $target->group_id,
            ]);
        } else {
            $category->transactions()->update([
                'category_id' => null,
                'group_id'    => null
            ]);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted']);
    }
}
