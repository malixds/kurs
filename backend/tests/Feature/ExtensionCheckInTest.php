<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtensionCheckInTest extends TestCase
{
    use RefreshDatabase;

    public function test_extension_check_in_requires_valid_secret_key(): void
    {
        $response = $this->postJson('/api/v1/extension/check-in', [
            'secret_key' => str_repeat('x', 48),
            'employee' => ['external_id' => 'emp-test'],
            'answers' => [],
        ]);

        $response->assertStatus(422);
    }

    public function test_dashboard_login_rejects_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/dashboard/auth/login', [
            'email' => 'missing@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
    }
}
