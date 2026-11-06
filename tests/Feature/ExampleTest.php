<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        $loginResponse = $this->get('/login');
        $loginResponse->assertStatus(200);

        $wakaUser = \App\Models\User::where('role_id', 1)->first();
        if ($wakaUser) {
            $dashResponse = $this->actingAs($wakaUser)->get('/waka/dashboard');
            $dashResponse->assertStatus(200);

            $classesResponse = $this->actingAs($wakaUser)->get('/waka/classes');
            $classesResponse->assertStatus(200);

            $schedulesResponse = $this->actingAs($wakaUser)->get('/waka/schedules');
            $schedulesResponse->assertStatus(200);

            $roomsResponse = $this->actingAs($wakaUser)->get('/waka/rooms');
            $roomsResponse->assertStatus(200);

            // Test web login with credentials
            $loginPost = $this->post('/login', [
                'username' => $wakaUser->username,
                'password' => 'password123',
            ]);
            $loginPost->assertRedirect('/waka/dashboard');

            $this->assertNotNull(\App\Models\Schedule::count());

            // Test logout via POST without CSRF token
            $logoutPostResponse = $this->post('/logout');
            $logoutPostResponse->assertRedirect('/login');

            // Test logout via GET (browser address bar navigation/refresh)
            $logoutGetResponse = $this->get('/logout');
            $logoutGetResponse->assertRedirect('/login');
        }
    }
}
