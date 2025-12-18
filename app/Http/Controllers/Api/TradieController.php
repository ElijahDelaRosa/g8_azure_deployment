<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tradie;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TradieController extends Controller
{
    /**
     * Fetch tradies with optional filters
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Tradie::query()->with('services');

        // Filter by status (default: active)
        $query->where('status', $request->input('status', 'active'));

        // Filter by availability
        if ($request->has('availability_status')) {
            $query->where('availability_status', $request->input('availability_status'));
        }

        // Filter by service
        if ($request->has('service_id')) {
            $query->withService($request->input('service_id'));
        }

        // Filter by region
        if ($request->has('region')) {
            $query->inRegion($request->input('region'));
        }

        // Filter by location (latitude, longitude, radius)
        if ($request->has(['latitude', 'longitude'])) {
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');
            $radius = $request->input('radius', 50); // Default 50km

            if ($request->boolean('use_service_radius', false)) {
                $query->withinServiceRadius($latitude, $longitude);
            } else {
                $query->nearLocation($latitude, $longitude, $radius);
            }
        }

        // Filter by hourly rate range
        if ($request->has('min_rate')) {
            $query->where('hourly_rate', '>=', $request->input('min_rate'));
        }
        if ($request->has('max_rate')) {
            $query->where('hourly_rate', '<=', $request->input('max_rate'));
        }

        // Filter by minimum years of experience
        if ($request->has('min_experience')) {
            $query->where('years_experience', '>=', $request->input('min_experience'));
        }

        // Search by name or business name
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('business_name', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $tradies = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $tradies,
        ]);
    }

    /**
     * Fetch a single tradie by ID
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $tradie = Tradie::with('services')
            ->where('status', 'active')
            ->find($id);

        if (!$tradie) {
            return response()->json([
                'success' => false,
                'message' => 'Tradie not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $tradie,
        ]);
    }
}
