<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_are_available(): void
    {
        $this->get('/')->assertOk();
        $this->get('/booking')->assertOk();
        $this->get('/tickets')->assertOk();
        $this->get('/privacy')->assertOk();
        $this->get('/login')->assertOk();
    }

    public function test_health_endpoint_is_available(): void
    {
        $this->getJson('/api/health')->assertOk()->assertJson(['status' => 'ok']);
    }
}
