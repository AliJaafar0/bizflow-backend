<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        $query = Task::with([
            'customer',
            'lead',
            'assignedUser',
        ])->where('company_id', $companyId);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->filled('lead_id')) {
            $query->where('lead_id', $request->lead_id);
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

            'lead_id' => [
                'nullable',
                Rule::exists('leads', 'id')
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
            'due_date' => 'nullable|date',

            'priority' => [
                'nullable',
                Rule::in([
                    'low',
                    'medium',
                    'high',
                ]),
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'pending',
                    'in_progress',
                    'completed',
                    'cancelled',
                ]),
            ],
        ]);

        $validated['company_id'] = $companyId;

        $task = Task::create($validated);

        return response()->json([
            'message' => 'Task created successfully',
            'task' => $task->load([
                'customer',
                'lead',
                'assignedUser',
            ]),
        ], 201);
    }

    public function show(Request $request, Task $task)
    {
        $this->ensureSameCompany($request, $task);

        return response()->json(
            $task->load([
                'customer',
                'lead',
                'assignedUser',
            ])
        );
    }

    public function update(Request $request, Task $task)
    {
        $this->ensureSameCompany($request, $task);

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

            'lead_id' => [
                'nullable',
                Rule::exists('leads', 'id')
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
            'due_date' => 'nullable|date',

            'priority' => [
                'sometimes',
                Rule::in([
                    'low',
                    'medium',
                    'high',
                ]),
            ],

            'status' => [
                'sometimes',
                Rule::in([
                    'pending',
                    'in_progress',
                    'completed',
                    'cancelled',
                ]),
            ],
        ]);

        $task->update($validated);

        return response()->json([
            'message' => 'Task updated successfully',
            'task' => $task->fresh()->load([
                'customer',
                'lead',
                'assignedUser',
            ]),
        ]);
    }

    public function destroy(Request $request, Task $task)
    {
        $this->ensureSameCompany($request, $task);

        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully',
        ]);
    }

    private function ensureSameCompany(
        Request $request,
        Task $task
    ): void {
        if ($task->company_id !== $request->user()->company_id) {
            abort(403, 'Unauthorized');
        }
    }
}