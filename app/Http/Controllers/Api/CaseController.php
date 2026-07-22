<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Analytics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaseController extends Controller
{
    /**
     * Display a listing of cases.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'dataset' => 'nullable|string|in:ccma,labour-court',
            'limit' => 'nullable|integer|min:1|max:1000',
            'page' => 'nullable|integer|min:1',
        ]);

        $dataset = $request->query('dataset');
        $limit = (int) $request->query('limit', 100);

        $query = Analytics::query();

        if ($dataset === 'ccma') {
            $query->whereIn('court', ['CCMA', 'Bargaining Council']);
        } elseif ($dataset === 'labour-court') {
            $query->whereIn('court', ['Labour Court', 'Labour Appeal Court']);
        }

        $cases = $query->orderBy('award_date', 'desc')->paginate($limit);

        return response()->json([
            'data' => $cases->items(),
            'meta' => [
                'current_page' => $cases->currentPage(),
                'last_page' => $cases->lastPage(),
                'per_page' => $cases->perPage(),
                'total' => $cases->total(),
            ],
        ]);
    }

    /**
     * Display the specified case.
     */
    public function show(string $id): JsonResponse
    {
        $case = Analytics::findOrFail($id);

        return response()->json([
            'data' => $case,
        ]);
    }
}
