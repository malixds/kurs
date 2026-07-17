<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_health_endpoint_responds(): void
    {
        $this->get('/up')->assertStatus(200);
    }

    public function test_home_redirects_guests_to_login(): void
    {
        $this->get('/')->assertRedirect();
    }
}
