<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Coin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppCoinController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'denomination' => ['nullable', 'integer', Rule::in(Coin::VALID_DENOMINATIONS)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $device = auth('app')->user();
        $query = $device->coins()->orderByDesc('deposited_at');

        if ($request->filled('denomination')) {
            $query->where('denomination', $request->integer('denomination'));
        }

        if ($request->filled('from')) {
            $query->where('deposited_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('deposited_at', '<=', $request->input('to'));
        }

        return response()->json(
            $query->paginate(min($request->integer('per_page', 50), 100))
        );
    }
}
