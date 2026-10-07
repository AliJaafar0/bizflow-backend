<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test Owner',
            'email' => 'owner@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'company_name' => 'Test Company',
            'industry' => 'Technology',
            'country' => 'Lebanon',
            'city' => 'Beirut',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'owner@test.com',
            'role' => 'owner',
        ]);

        $this->assertDatabaseHas('companies', [
            'name' => 'Test Company',
        ]);
    }

    public function test_user_can_login_with_correct_password(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
            'email' => 'company@test.com',
        ]);

        User::create([
            'company_id' => $company->id,
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => 'password123',
            'role' => 'owner',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'user@test.com',
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'user',
                'token',
            ]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
        ]);

        User::create([
            'company_id' => $company->id,
            'name' => 'Test User',
            'email' => 'wrong@test.com',
            'password' => 'password123',
            'role' => 'owner',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'wrong@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(401);
    }

    public function test_unverified_user_cannot_access_dashboard(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
        ]);

        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Unverified User',
            'email' => 'unverified@test.com',
            'password' => 'password123',
            'role' => 'owner',
            'is_active' => true,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/dashboard');

        $response->assertStatus(403);
    }

    public function test_verified_user_can_access_dashboard(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
        ]);

        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Verified User',
            'email' => 'verified@test.com',
            'password' => 'password123',
            'role' => 'owner',
            'is_active' => true,
        ]);

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/dashboard');

        $response->assertOk();
    }
}