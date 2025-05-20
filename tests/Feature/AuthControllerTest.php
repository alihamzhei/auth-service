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
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the AuthService
        $this->authServiceMock = Mockery::mock(AuthService::class);
        $this->app->instance(AuthService::class, $this->authServiceMock);
    }

    public function test_user_can_register()
    {
        // Setup mock response
        $mockResponse = [
            'id' => 'test-uuid',
            'email' => 'test@example.com'
        ];
        
        $this->authServiceMock
            ->shouldReceive('register')
            ->once()
            ->andReturn($mockResponse);

        $response = $this->postJson('/auth/register', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(201)
            ->assertJson($mockResponse);
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
    }

    public function test_user_can_login()
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
            ->andReturn($mockResponse);

        $response = $this->postJson('/auth/login', [
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

        $response = $this->postJson('/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong_password'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Invalid credentials'
            ]);
    }

    public function test_refresh_token_validates_input()
    {
        $this->actingAs(User::factory()->create());
        
        $response = $this->postJson('/auth/refresh', []);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['refresh_token']);
    }

    public function test_user_can_refresh_token()
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
            ->with($user->id, 'valid-refresh-token')
            ->andReturn($mockResponse);

        $response = $this->postJson('/auth/refresh', [
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
            ->with($user->id, 'invalid-refresh-token')
            ->andThrow(new \Exception('Invalid refresh token'));

        $response = $this->postJson('/auth/refresh', [
            'refresh_token' => 'invalid-refresh-token'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Invalid refresh token'
            ]);
    }

    public function test_user_can_logout()
    {
        // Create a user and act as them
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $this->authServiceMock
            ->shouldReceive('logout')
            ->once()
            ->with($user->id, 'valid-refresh-token')
            ->andReturn(true);

        $response = $this->postJson('/auth/logout', [
            'refresh_token' => 'valid-refresh-token'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Successfully logged out'
            ]);
    }

    public function test_validate_token_returns_user_info()
    {
        // Create a user with roles and act as them
        $user = User::factory()->create([
            'email' => 'test@example.com'
        ]);
        
        // Mock the getRoleNames method to return roles
        $user->shouldReceive('getRoleNames')
            ->once()
            ->andReturn(['user']);
            
        $this->actingAs($user);

        $response = $this->postJson('/auth/validate');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'email' => 'test@example.com',
                'roles' => ['user']
            ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}