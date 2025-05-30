<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Application\Services\AuthService;
use App\Application\DTOs\RegisterUserDTO;
use App\Application\DTOs\LoginUserDTO;
use Mockery;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;
    
    protected $authServiceMock;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the AuthService
        $this->authServiceMock = Mockery::mock(AuthService::class);
        $this->app->instance(AuthService::class, $this->authServiceMock);
    }

    public function test_user_can_register_with_valid_data()
    {
        // Setup mock response
        $mockResponse = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'email' => 'test@example.com'
        ];
        
        $this->authServiceMock
            ->shouldReceive('register')
            ->once()
            ->with(Mockery::type(RegisterUserDTO::class))
            ->andReturn($mockResponse);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(201)
            ->assertJson($mockResponse);
    }

    public function test_register_fails_with_invalid_email()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_fails_with_short_password()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'valid@example.com',
            'password' => 'short'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_fails_with_missing_fields()
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_register_handles_service_exceptions()
    {
        $this->authServiceMock
            ->shouldReceive('register')
            ->once()
            ->andThrow(new \Exception('User already exists'));

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'User already exists'
            ]);
    }

    public function test_user_can_login_with_valid_credentials()
    {
        // Setup mock response
        $mockResponse = [
            'access_token' => 'mock-access-token',
            'refresh_token' => 'mock-refresh-token',
            'token_type' => 'bearer',
            'expires_in' => 3600
        ];
        
        $this->authServiceMock
            ->shouldReceive('login')
            ->once()
            ->with(Mockery::type(LoginUserDTO::class))
            ->andReturn($mockResponse);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJson($mockResponse);
    }

    public function test_login_fails_with_invalid_credentials()
    {
        $this->authServiceMock
            ->shouldReceive('login')
            ->once()
            ->andThrow(new \Exception('Invalid credentials'));

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong_password'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Invalid credentials'
            ]);
    }

    public function test_login_validates_input()
    {
        // Test with missing email
        $response = $this->postJson('/api/auth/login', [
            'password' => 'password123'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Test with missing password
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        // Test with invalid email format
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid-email',
            'password' => 'password123'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_refresh_token_requires_authentication()
    {
        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => 'some-token'
        ]);

        $response->assertStatus(401);
    }

    public function test_refresh_token_validates_input()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $response = $this->postJson('/api/auth/refresh', []);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['refresh_token']);
    }

    public function test_user_can_refresh_token_with_valid_token()
    {
        // Create a user and act as them
        $user = User::factory()->create();
        $this->actingAs($user);
        
        // Setup mock response
        $mockResponse = [
            'access_token' => 'new-mock-access-token',
            'refresh_token' => 'new-mock-refresh-token',
            'token_type' => 'bearer',
            'expires_in' => 3600
        ];
        
        $this->authServiceMock
            ->shouldReceive('refresh')
            ->once()
            ->with($user->getKey(), 'valid-refresh-token')
            ->andReturn($mockResponse);

        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => 'valid-refresh-token'
        ]);

        $response->assertStatus(200)
            ->assertJson($mockResponse);
    }

    public function test_refresh_token_fails_with_invalid_token()
    {
        // Create a user and act as them
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $this->authServiceMock
            ->shouldReceive('refresh')
            ->once()
            ->with($user->getKey(), 'invalid-refresh-token')
            ->andThrow(new \Exception('Invalid refresh token'));

        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => 'invalid-refresh-token'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Invalid refresh token'
            ]);
    }

    public function test_logout_requires_authentication()
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    public function test_user_can_logout_with_refresh_token()
    {
        // Create a user and act as them
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $this->authServiceMock
            ->shouldReceive('logout')
            ->once()
            ->with($user->getKey(), 'valid-refresh-token')
            ->andReturn();

        $response = $this->postJson('/api/auth/logout', [
            'refresh_token' => 'valid-refresh-token'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Successfully logged out'
            ]);
    }

    public function test_user_can_logout_without_refresh_token()
    {
        // Create a user and act as them
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $this->authServiceMock
            ->shouldReceive('logout')
            ->once()
            ->with($user->getKey(), null)
            ->andReturn();

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Successfully logged out'
            ]);
    }

    public function test_validate_requires_authentication()
    {
        $response = $this->postJson('/api/auth/validate');

        $response->assertStatus(401);
    }

    public function test_validate_returns_user_info_for_authenticated_user()
    {
        // Create a user and act as them
        $user = User::factory()->create([
            'email' => 'test@example.com'
        ]);
        
        $this->actingAs($user);

        $response = $this->postJson('/api/auth/validate');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'email',
                'roles'
            ])
            ->assertJson([
                'id' => $user->getKey(),
                'email' => 'test@example.com'
            ]);
    }

    public function test_all_endpoints_return_json_responses()
    {
        // Test that all endpoints return proper JSON responses
        $endpoints = [
            ['POST', '/api/auth/register', ['name' => 'Test User', 'email' => 'test@example.com', 'password' => 'password123']],
            ['POST', '/api/auth/login', ['email' => 'test@example.com', 'password' => 'password123']],
        ];

        foreach ($endpoints as [$method, $endpoint, $data]) {
            $response = $this->json($method, $endpoint, $data);
            $this->assertTrue(
                $response->headers->get('Content-Type') === 'application/json' ||
                str_contains($response->headers->get('Content-Type'), 'application/json'),
                "Endpoint {$method} {$endpoint} should return JSON response"
            );
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}