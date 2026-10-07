<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenantSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function createVerifiedUser(
        Company $company,
        string $email,
        string $role = 'owner'
    ): array {
        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Test User',
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

    public function test_company_cannot_view_customer_from_another_company(): void
    {
        $companyA = Company::create([
            'name' => 'Company A',
        ]);

        $companyB = Company::create([
            'name' => 'Company B',
        ]);

        [$userA, $tokenA] = $this->createVerifiedUser(
            $companyA,
            'owner-a@test.com'
        );

        $customerB = Customer::create([
            'company_id' => $companyB->id,
            'name' => 'Company B Customer',
            'email' => 'customer-b@test.com',
            'status' => 'active',
        ]);

        $response = $this
            ->withHeader(
                'Authorization',
                'Bearer ' . $tokenA
            )
            ->getJson(
                '/api/customers/' . $customerB->id
            );

        $response->assertStatus(403);
    }

    public function test_company_only_sees_its_own_customers(): void
    {
        $companyA = Company::create([
            'name' => 'Company A',
        ]);

        $companyB = Company::create([
            'name' => 'Company B',
        ]);

        [$userA, $tokenA] = $this->createVerifiedUser(
            $companyA,
            'owner-a@test.com'
        );

        Customer::create([
            'company_id' => $companyA->id,
            'name' => 'Company A Customer',
            'email' => 'customer-a@test.com',
            'status' => 'active',
        ]);

        Customer::create([
            'company_id' => $companyB->id,
            'name' => 'Company B Customer',
            'email' => 'customer-b@test.com',
            'status' => 'active',
        ]);

        $response = $this
            ->withHeader(
                'Authorization',
                'Bearer ' . $tokenA
            )
            ->getJson('/api/customers');

        $response
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Company A Customer',
            ])
            ->assertJsonMissing([
                'name' => 'Company B Customer',
            ]);
    }

    public function test_customer_cannot_be_assigned_to_user_from_another_company(): void
    {
        $companyA = Company::create([
            'name' => 'Company A',
        ]);

        $companyB = Company::create([
            'name' => 'Company B',
        ]);

        [$userA, $tokenA] = $this->createVerifiedUser(
            $companyA,
            'owner-a@test.com'
        );

        [$userB, $tokenB] = $this->createVerifiedUser(
            $companyB,
            'owner-b@test.com'
        );

        $response = $this
            ->withHeader(
                'Authorization',
                'Bearer ' . $tokenA
            )
            ->postJson('/api/customers', [
                'name' => 'Illegal Assignment Customer',
                'email' => 'illegal@test.com',
                'status' => 'active',
                'assigned_to' => $userB->id,
            ]);

        $response->assertStatus(422);

        $this->assertDatabaseMissing('customers', [
            'email' => 'illegal@test.com',
        ]);
    }

    public function test_lead_cannot_use_customer_from_another_company(): void
    {
        $companyA = Company::create([
            'name' => 'Company A',
        ]);

        $companyB = Company::create([
            'name' => 'Company B',
        ]);

        [$userA, $tokenA] = $this->createVerifiedUser(
            $companyA,
            'owner-a@test.com'
        );

        $customerB = Customer::create([
            'company_id' => $companyB->id,
            'name' => 'Foreign Customer',
            'email' => 'foreign@test.com',
            'status' => 'active',
        ]);

        $response = $this
            ->withHeader(
                'Authorization',
                'Bearer ' . $tokenA
            )
            ->postJson('/api/leads', [
                'customer_id' => $customerB->id,
                'title' => 'Illegal Lead',
                'value' => 1000,
                'stage' => 'new',
            ]);

        $response->assertStatus(422);

        $this->assertDatabaseMissing('leads', [
            'title' => 'Illegal Lead',
        ]);
    }
}