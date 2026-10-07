<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        $query = Customer::with('assignedUser')
            ->where('company_id', $companyId);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $perPage = $request->integer('per_page', 10);

        if ($perPage < 1) {
            $perPage = 10;
        }

        if ($perPage > 100) {
            $perPage = 100;
        }

        return response()->json(
            $query->latest()
                ->paginate($perPage)
                ->withQueryString()
        );
    }

    public function store(Request $request)
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:100',
            'status' => [
                'nullable',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
            'notes' => 'nullable|string',

            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')
                    ->where(
                        fn ($query) =>
                        $query->where('company_id', $companyId)
                    ),
            ],
        ]);

        $validated['company_id'] = $companyId;

        $customer = Customer::create($validated);

        return response()->json([
            'message' => 'Customer created successfully',
            'customer' => $customer->load('assignedUser'),
        ], 201);
    }

    public function show(Request $request, Customer $customer)
    {
        $this->ensureSameCompany($request, $customer);

        return response()->json(
            $customer->load([
                'assignedUser',
                'leads',
                'appointments',
                'tasks',
            ])
        );
    }

    public function update(Request $request, Customer $customer)
    {
        $this->ensureSameCompany($request, $customer);

        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:100',

            'status' => [
                'sometimes',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],

            'notes' => 'nullable|string',

            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')
                    ->where(
                        fn ($query) =>
                        $query->where('company_id', $companyId)
                    ),
            ],
        ]);

        $customer->update($validated);

        return response()->json([
            'message' => 'Customer updated successfully',
            'customer' => $customer->fresh()->load('assignedUser'),
        ]);
    }

    public function destroy(Request $request, Customer $customer)
    {
        $this->ensureSameCompany($request, $customer);

        $customer->delete();

        return response()->json([
            'message' => 'Customer deleted successfully',
        ]);
    }

    private function ensureSameCompany(
        Request $request,
        Customer $customer
    ): void {
        if ($customer->company_id !== $request->user()->company_id) {
            abort(403, 'Unauthorized');
        }
    }
}