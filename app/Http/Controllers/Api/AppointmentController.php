<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        $query = Appointment::with([
            'customer',
            'assignedUser',
        ])->where('company_id', $companyId);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('starts_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('starts_at', '<=', $request->date_to);
        }

        $perPage = $request->integer('per_page', 10);

        if ($perPage < 1) {
            $perPage = 10;
        }

        if ($perPage > 100) {
            $perPage = 100;
        }

        return response()->json(
            $query->orderBy('starts_at')
                ->paginate($perPage)
                ->withQueryString()
        );
    }

    public function store(Request $request)
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'customer_id' => [
                'nullable',
                Rule::exists('customers', 'id')
                    ->where(
                        fn ($query) =>
                        $query->where('company_id', $companyId)
                    ),
            ],

            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')
                    ->where(
                        fn ($query) =>
                        $query->where('company_id', $companyId)
                    ),
            ],

            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',

            'status' => [
                'nullable',
                Rule::in([
                    'scheduled',
                    'completed',
                    'cancelled',
                ]),
            ],

            'location' => 'nullable|string|max:255',
        ]);

        $validated['company_id'] = $companyId;

        $appointment = Appointment::create($validated);

        return response()->json([
            'message' => 'Appointment created successfully',
            'appointment' => $appointment->load([
                'customer',
                'assignedUser',
            ]),
        ], 201);
    }

    public function show(Request $request, Appointment $appointment)
    {
        $this->ensureSameCompany($request, $appointment);

        return response()->json(
            $appointment->load([
                'customer',
                'assignedUser',
            ])
        );
    }

    public function update(Request $request, Appointment $appointment)
    {
        $this->ensureSameCompany($request, $appointment);

        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'customer_id' => [
                'nullable',
                Rule::exists('customers', 'id')
                    ->where(
                        fn ($query) =>
                        $query->where('company_id', $companyId)
                    ),
            ],

            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')
                    ->where(
                        fn ($query) =>
                        $query->where('company_id', $companyId)
                    ),
            ],

            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'starts_at' => 'sometimes|required|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',

            'status' => [
                'sometimes',
                Rule::in([
                    'scheduled',
                    'completed',
                    'cancelled',
                ]),
            ],

            'location' => 'nullable|string|max:255',
        ]);

        $appointment->update($validated);

        return response()->json([
            'message' => 'Appointment updated successfully',
            'appointment' => $appointment->fresh()->load([
                'customer',
                'assignedUser',
            ]),
        ]);
    }

    public function destroy(Request $request, Appointment $appointment)
    {
        $this->ensureSameCompany($request, $appointment);

        $appointment->delete();

        return response()->json([
            'message' => 'Appointment deleted successfully',
        ]);
    }

    private function ensureSameCompany(
        Request $request,
        Appointment $appointment
    ): void {
        if ($appointment->company_id !== $request->user()->company_id) {
            abort(403, 'Unauthorized');
        }
    }
}