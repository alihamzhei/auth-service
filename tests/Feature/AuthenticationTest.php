<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Mockery;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $response = $this->postJson('/auth/register', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'email'
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com'
        ]);
    }

    public function test_register_validates_input()
    {
        // Test with invalid email
        $response = $this->postJson('/auth/register', [
            'email' => 'invalid-email',
            'password' => 'password123'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Test with short password
        $response = $this->postJson('/auth/register', [
            'email' => 'valid@example.com',
            'password' => 'short'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        // Test with missing fields
        $response = $this->postJson('/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_user_can_login()
    {
        // Create a user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in'
            ]);
    }

    public function test_login_fails_with_invalid_credentials()
    {
        // Create a user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        // Test with wrong password
        $response = $this->postJson('/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong_password'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Invalid credentials'
            ]);

        // Test with non-existent email
        $response = $this->postJson('/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_refresh_token()
    {
        // Create a user and generate tokens
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        
        $this->actingAs($user, 'api')
            ->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/auth/refresh', [
                'refresh_token' => 'valid_refresh_token'
            ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in'
            ]);
    }

    public function test_user_can_logout()
    {
        // Create a user and generate token
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        
        $this->actingAs($user, 'api')
            ->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/auth/logout')
            ->assertStatus(200)
            ->assertJson([
                'message' => 'Successfully logged out'
            ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
