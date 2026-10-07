<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            User::where('company_id', $request->user()->company_id)
                ->select(
                    'id',
                    'name',
                    'email',
                    'role',
                    'is_active',
                    'created_at'
                )
                ->latest()
                ->get()
        );
    }

    public function store(Request $request)
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => 'required|string|min:8|confirmed',

            'role' => [
                'required',
                Rule::in(['admin', 'member']),
            ],
        ]);

        $user = User::create([
            'company_id' => $companyId,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Team member created successfully',
            'user' => $user,
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        $this->ensureSameCompany($request, $user);

        if ($user->role === 'owner') {
            return response()->json([
                'message' => 'Owner account cannot be modified here',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',

            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],

            'role' => [
                'sometimes',
                Rule::in(['admin', 'member']),
            ],

            'is_active' => 'sometimes|boolean',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Team member updated successfully',
            'user' => $user,
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        $this->ensureSameCompany($request, $user);

        if ($user->role === 'owner') {
            return response()->json([
                'message' => 'Owner account cannot be deleted',
            ], 403);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'Team member deleted successfully',
        ]);
    }

    private function ensureSameCompany(Request $request, User $user): void
    {
        if ($user->company_id !== $request->user()->company_id) {
            abort(403, 'Unauthorized');
        }
    }
}