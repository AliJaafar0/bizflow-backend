<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        $query = Lead::with([
            'customer',
            'assignedUser',
        ])
        ->where('company_id', $companyId);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($request->filled('stage')) {
            $query->where('stage', $request->stage);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
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
            'customer_id' => [
                'nullable',
                Rule::exists('customers', 'id')
                    ->where(
                        fn ($query) =>
                        $query->where('company_id', $companyId)
                    ),
            ],

            'title' => 'required|string|max:255',

            'value' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'stage' => [
                'nullable',
                Rule::in([
                    'new',
                    'qualified',
                    'proposal',
                    'negotiation',
                    'won',
                    'lost',
                ]),
            ],

            'source' => 'nullable|string|max:100',
            'notes' => 'nullable|string',

            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')
                    ->where(
                        fn ($query) =>
                        $query->where('company_id', $companyId)
                    ),
            ],

            'expected_close_date' => 'nullable|date',
        ]);

        $validated['company_id'] = $companyId;

        $lead = Lead::create($validated);

        return response()->json([
            'message' => 'Lead created successfully',
            'lead' => $lead->load([
                'customer',
                'assignedUser',
            ]),
        ], 201);
    }

    public function show(Request $request, Lead $lead)
    {
        $this->ensureSameCompany($request, $lead);

        return response()->json(
            $lead->load([
                'customer',
                'assignedUser',
                'tasks',
            ])
        );
    }

    public function update(Request $request, Lead $lead)
    {
        $this->ensureSameCompany($request, $lead);

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

            'title' => 'sometimes|required|string|max:255',

            'value' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'stage' => [
                'sometimes',
                Rule::in([
                    'new',
                    'qualified',
                    'proposal',
                    'negotiation',
                    'won',
                    'lost',
                ]),
            ],

            'source' => 'nullable|string|max:100',
            'notes' => 'nullable|string',

            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')
                    ->where(
                        fn ($query) =>
                        $query->where('company_id', $companyId)
                    ),
            ],

            'expected_close_date' => 'nullable|date',
        ]);

        $lead->update($validated);

        return response()->json([
            'message' => 'Lead updated successfully',
            'lead' => $lead->fresh()->load([
                'customer',
                'assignedUser',
            ]),
        ]);
    }

    public function destroy(Request $request, Lead $lead)
    {
        $this->ensureSameCompany($request, $lead);

        $lead->delete();

        return response()->json([
            'message' => 'Lead deleted successfully',
        ]);
    }

    private function ensureSameCompany(
        Request $request,
        Lead $lead
    ): void {
        if ($lead->company_id !== $request->user()->company_id) {
            abort(403, 'Unauthorized');
        }
    }
}