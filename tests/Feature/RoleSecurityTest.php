<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function createVerifiedUser(
        Company $company,
        string $email,
        string $role
    ): array {
        $user = User::create([
            'company_id' => $company->id,
            'name' => ucfirst($role) . ' User',
            'email' => $email,
            'password' => 'password123',
            'role' => $role,
            'is_active' => true,
        ]);

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        $token = $user
            ->createToken('test-token')
            ->plainTextToken;

        return [$user, $token];
    }

    public function test_owner_can_access_team_management(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
        ]);

        [$owner, $token] = $this->createVerifiedUser(
            $company,
            'owner@test.com',
            'owner'
        );

        $response = $this
            ->withHeader(
                'Authorization',
                'Bearer ' . $token
            )
            ->getJson('/api/team');

        $response->assertOk();
    }

    public function test_admin_can_access_team_management(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
        ]);

        [$admin, $token] = $this->createVerifiedUser(
            $company,
            'admin@test.com',
            'admin'
        );

        $response = $this
            ->withHeader(
                'Authorization',
                'Bearer ' . $token
            )
            ->getJson('/api/team');

        $response->assertOk();
    }

    public function test_member_cannot_access_team_management(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
        ]);

        [$member, $token] = $this->createVerifiedUser(
            $company,
            'member@test.com',
            'member'
        );

        $response = $this
            ->withHeader(
                'Authorization',
                'Bearer ' . $token
            )
            ->getJson('/api/team');

        $response->assertStatus(403);
    }

    public function test_owner_can_create_team_member(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
        ]);

        [$owner, $token] = $this->createVerifiedUser(
            $company,
            'owner@test.com',
            'owner'
        );

        $response = $this
            ->withHeader(
                'Authorization',
                'Bearer ' . $token
            )
            ->postJson('/api/team', [
                'name' => 'New Member',
                'email' => 'newmember@test.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'member',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'newmember@test.com',
            'role' => 'member',
            'company_id' => $company->id,
        ]);
    }

    public function test_member_cannot_create_team_member(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
        ]);

        [$member, $token] = $this->createVerifiedUser(
            $company,
            'member@test.com',
            'member'
        );

        $response = $this
            ->withHeader(
                'Authorization',
                'Bearer ' . $token
            )
            ->postJson('/api/team', [
                'name' => 'Illegal Member',
                'email' => 'illegal@test.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'member',
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('users', [
            'email' => 'illegal@test.com',
        ]);
    }
}