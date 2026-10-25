<?php

namespace App\Http\Controllers;

use App\Services\DashboardLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardLocationController extends Controller
{
    public function __invoke(Request $request, DashboardLocationService $locations): JsonResponse
    {
        $validated = $request->validate([
            'land_id' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json($locations->loadForUser(
            $request->user(),
            isset($validated['land_id']) ? (int) $validated['land_id'] : null,
        ));
    }
}
