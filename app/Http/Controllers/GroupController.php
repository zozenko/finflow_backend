<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GroupController extends Controller
{
    /**
     * Get all groups for the authenticated user
     */
    public function index(Request $request)
    {
        return response()->json(
            $request->user()->groups()
                ->orderBy('sort_order', 'asc')
                ->orderBy('name', 'asc')
                ->get()
        );
    }

    /**
     * Store a new group
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('groups')->where(fn($q) => $q->where('user_id', $request->user()->id))
            ],
            'icon_key'   => 'nullable|string|max:50',
            'color'      => 'nullable|string|max:7',
            'sort_order' => 'nullable|integer',
        ]);

        $group = $request->user()->groups()->create([
            'name'       => $fields['name'],
            'icon_key'   => $fields['icon_key'] ?? 'home',
            'color'      => $fields['color'] ?? '#3b82f6',
            'sort_order' => $fields['sort_order'] ?? 0,
        ]);

        return response()->json($group, 201);
    }

    /**
     * Update an existing group
     */
    public function update(Request $request, Group $group)
    {
        // Authorization check
        if ($group->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $fields = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('groups')->where(fn($q) => $q->where('user_id', $request->user()->id))
                    ->ignore($group->id)
            ],
            'icon_key'   => 'sometimes|string|max:50',
            'color'      => 'sometimes|nullable|string|max:7',
            'sort_order' => 'sometimes|required|integer',
        ]);

        $group->update($fields);

        return response()->json($group);
    }

    /**
     * Remove the specified group
     */
    public function destroy(Request $request, Group $group)
    {
        if ($group->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $group->delete();

        return response()->json(['message' => 'Group deleted successfully.']);
    }
}
