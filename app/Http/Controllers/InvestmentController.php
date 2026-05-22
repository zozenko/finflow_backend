<?php

namespace App\Http\Controllers;

use App\Models\Investment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class InvestmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'nullable|string|in:crypto,stocks,bonds,etf,fiat',
        ]);

        $investments = $request->user()->investments()
            ->when($validated['type'] ?? null, function ($query, $type) {
                return $query->where('type', $type);
            })
            ->orderBy('purchase_date', 'desc')
            ->get();

        return response()->json($investments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:crypto,stocks,bonds,etf,fiat',
            'quantity' => 'required|numeric|min:0',
            'purchase_date' => 'nullable|date',
        ]);

        $investment = $request->user()->investments()->create(array_filter($validated));

        return response()->json($investment, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Investment $investment): JsonResponse
    {
        if ($investment->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized action.');
        }

        return response()->json($investment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Investment $investment): JsonResponse
    {
        if ($investment->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => ['sometimes', 'required', Rule::in(['crypto', 'stocks', 'bonds', 'etf', 'fiat'])],
            'quantity' => 'sometimes|required|numeric|min:0',
            'purchase_date' => 'sometimes|nullable|date',
        ]);

        $investment->update($validated);

        return response()->json($investment);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Investment $investment): JsonResponse
    {
        if ($investment->user_id !== $request->user()->id) {
            abort(403);
        }
        $investment->delete();
        return response()->json(null, 204);
    }
}
