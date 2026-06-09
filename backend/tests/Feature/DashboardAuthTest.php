<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_for_company_user(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'hr',
        ]);
        $company->members()->attach($user->id, ['role' => 'hr']);

        $response = $this->postJson('/api/v1/dashboard/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()->assertJsonStructure(['token', 'user']);
    }

    public function test_login_rejects_user_without_company(): void
    {
        $user = User::factory()->create(['company_id' => null]);

        $response = $this->postJson('/api/v1/dashboard/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(422);
    }

    public function test_me_requires_token(): void
    {
        $this->getJson('/api/v1/dashboard/auth/me')->assertUnauthorized();
    }
}
