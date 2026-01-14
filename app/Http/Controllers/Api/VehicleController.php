<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    /**
     * Display a listing of vehicles.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Vehicle::select([
            'id',
            'customer_id',
            'vehicle_number',
            'chassis_number',
            'make',
            'model',
            'year',
            'color',
            'notes',
            'is_active',
            'created_at',
        ])->with(['customer:id,customer_code,company_name,first_name,last_name']);

        // Filter by customer
        if ($request->has('customer_id')) {
            $query->forCustomer($request->customer_id);
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        } else {
            $query->active(); // Default to active vehicles
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'vehicle_number');
        $sortOrder = $request->get('sort_order', 'asc');
        $validSortFields = ['vehicle_number', 'chassis_number', 'make', 'model', 'year', 'created_at'];
        
        if (in_array($sortBy, $validSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $vehicles = $query->paginate($perPage);

        // Append computed values
        $vehicles->getCollection()->transform(function ($vehicle) {
            $vehicle->display_name = $vehicle->display_name;
            $vehicle->full_identifier = $vehicle->full_identifier;
            $vehicle->transaction_count = $vehicle->transactions()->count();
            return $vehicle;
        });

        return response()->json($vehicles);
    }

    /**
     * Store a newly created vehicle.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'vehicle_number' => 'required|string|max:50',
            'chassis_number' => 'required|string|max:100|unique:vehicles,chassis_number',
            'make' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $vehicle = Vehicle::create($validator->validated());

        $vehicle->load('customer:id,customer_code,company_name,first_name,last_name');
        $vehicle->display_name = $vehicle->display_name;
        $vehicle->full_identifier = $vehicle->full_identifier;

        return response()->json([
            'message' => 'Vehicle created successfully',
            'data' => $vehicle,
        ], 201);
    }

    /**
     * Display the specified vehicle.
     */
    public function show(string $id): JsonResponse
    {
        $vehicle = Vehicle::with([
            'customer:id,customer_code,company_name,first_name,last_name',
            'transactions' => function ($query) {
                $query->select([
                    'id',
                    'date',
                    'account_id',
                    'vehicle_id',
                    'description',
                    'debit_amount',
                    'credit_amount',
                    'reference_number',
                ])->orderBy('date', 'desc')->limit(10);
            },
        ])->find($id);

        if (!$vehicle) {
            return response()->json([
                'message' => 'Vehicle not found',
            ], 404);
        }

        $vehicle->display_name = $vehicle->display_name;
        $vehicle->full_identifier = $vehicle->full_identifier;
        $vehicle->transaction_count = $vehicle->transactions()->count();

        return response()->json([
            'data' => $vehicle,
        ]);
    }

    /**
     * Update the specified vehicle.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            return response()->json([
                'message' => 'Vehicle not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'customer_id' => 'sometimes|exists:customers,id',
            'vehicle_number' => 'sometimes|string|max:50',
            'chassis_number' => 'sometimes|string|max:100|unique:vehicles,chassis_number,' . $id,
            'make' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $vehicle->update($validator->validated());
        $vehicle->load('customer:id,customer_code,company_name,first_name,last_name');
        $vehicle->display_name = $vehicle->display_name;
        $vehicle->full_identifier = $vehicle->full_identifier;

        return response()->json([
            'message' => 'Vehicle updated successfully',
            'data' => $vehicle,
        ]);
    }

    /**
     * Remove the specified vehicle.
     */
    public function destroy(string $id): JsonResponse
    {
        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            return response()->json([
                'message' => 'Vehicle not found',
            ], 404);
        }

        // Check if vehicle has transactions
        if ($vehicle->transactions()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete vehicle with existing transactions. Please deactivate it instead.',
            ], 422);
        }

        $vehicle->delete();

        return response()->json([
            'message' => 'Vehicle deleted successfully',
        ]);
    }

    /**
     * Get vehicles for a specific customer.
     */
    public function getByCustomer(string $customerId): JsonResponse
    {
        $vehicles = Vehicle::select([
            'id',
            'customer_id',
            'vehicle_number',
            'chassis_number',
            'make',
            'model',
            'year',
            'color',
            'is_active',
        ])
        ->forCustomer($customerId)
        ->active()
        ->orderBy('vehicle_number')
        ->get();

        $vehicles->transform(function ($vehicle) {
            $vehicle->display_name = $vehicle->display_name;
            $vehicle->full_identifier = $vehicle->full_identifier;
            return $vehicle;
        });

        return response()->json([
            'data' => $vehicles,
        ]);
    }
}
