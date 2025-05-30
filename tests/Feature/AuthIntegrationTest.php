<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Domain\Interfaces\TokenStorageInterface;
use App\Infrastructure\Repositories\TestTokenStorage;
use Spatie\Permission\Models\Role;

class AuthIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Bind test token storage
        $this->app->bind(TokenStorageInterface::class, TestTokenStorage::class);
        
        // Clear any existing tokens
        TestTokenStorage::clearAll();
    }

    public function test_complete_auth_flow_registration_to_logout()
    {
        // Step 1: Register a new user
        $registerResponse = $this->postJson('/api/auth/register', [
            'name' => 'Integration Test User',
            'email' => 'integration@example.com',
            'password' => 'password123'
        ]);

        $registerResponse->assertStatus(201)
            ->assertJsonStructure(['id', 'email'])
            ->assertJson(['email' => 'integration@example.com']);

        $userId = $registerResponse->json('id');
        $this->assertNotNull($userId);
        $this->assertIsString($userId);
        // Verify it's a UUID format
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $userId);

        // Step 2: Login with the created user
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'integration@example.com',
            'password' => 'password123'
        ]);


        $loginResponse->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in'
            ]);

        $accessToken = $loginResponse->json('access_token');
        $refreshToken = $loginResponse->json('refresh_token');
        $this->assertNotNull($accessToken);
        $this->assertNotNull($refreshToken);

        // Step 3: Validate the access token
        $validateResponse = $this->postJson('/api/auth/validate', [], [
            'Authorization' => 'Bearer ' . $accessToken
        ]);

        $validateResponse->assertStatus(200)
            ->assertJsonStructure(['id', 'email', 'roles'])
            ->assertJson([
                'id' => $userId,
                'email' => 'integration@example.com'
            ]);

        // Step 4: Refresh the access token
        $refreshResponse = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken
        ], [
            'Authorization' => 'Bearer ' . $accessToken
        ]);

        $refreshResponse->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in'
            ]);

        $newAccessToken = $refreshResponse->json('access_token');
        $newRefreshToken = $refreshResponse->json('refresh_token');
        $this->assertNotNull($newAccessToken);
        $this->assertNotNull($newRefreshToken);
        $this->assertNotEquals($accessToken, $newAccessToken);
        $this->assertNotEquals($refreshToken, $newRefreshToken);

        // Step 5: Validate the new access token
        $validateNewResponse = $this->postJson('/api/auth/validate', [], [
            'Authorization' => 'Bearer ' . $newAccessToken
        ]);

        $validateNewResponse->assertStatus(200)
            ->assertJson([
                'id' => $userId,
                'email' => 'integration@example.com'
            ]);

        // Step 6: Logout with the new refresh token
        $logoutResponse = $this->postJson('/api/auth/logout', [
            'refresh_token' => $newRefreshToken
        ], [
            'Authorization' => 'Bearer ' . $newAccessToken
        ]);

        $logoutResponse->assertStatus(200)
            ->assertJson(['message' => 'Successfully logged out']);

        // Step 7: Verify the old refresh token is invalid after refresh
        $oldRefreshResponse = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken
        ], [
            'Authorization' => 'Bearer ' . $newAccessToken
        ]);

        $oldRefreshResponse->assertStatus(401)
            ->assertJson(['error' => 'Invalid refresh token']);
    }

    public function test_login_with_non_existent_user_fails()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid credentials']);
    }

    public function test_login_with_wrong_password_fails()
    {
        // Create a user
        User::factory()->create([
            'email' => 'testuser@example.com',
            'password' => bcrypt('correctpassword')
        ]);

        // Try to login with wrong password
        $response = $this->postJson('/api/auth/login', [
            'email' => 'testuser@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid credentials']);
    }

    public function test_refresh_with_invalid_token_fails()
    {
        // Create and login user
        User::factory()->create([
            'email' => 'refreshtest@example.com',
            'password' => bcrypt('password123')
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'refreshtest@example.com',
            'password' => 'password123'
        ]);

        $accessToken = $loginResponse->json('access_token');

        // Try to refresh with invalid token
        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => 'invalid-refresh-token'
        ], [
            'Authorization' => 'Bearer ' . $accessToken
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid refresh token']);
    }

    public function test_validate_with_invalid_token_fails()
    {
        $response = $this->postJson('/api/auth/validate', [], [
            'Authorization' => 'Bearer invalid-jwt-token'
        ]);

        $response->assertStatus(401);
    }

    public function test_endpoints_require_authentication()
    {
        $protectedEndpoints = [
            ['POST', '/api/auth/refresh', ['refresh_token' => 'some-token']],
            ['POST', '/api/auth/logout', []],
            ['POST', '/api/auth/validate', []],
        ];

        foreach ($protectedEndpoints as [$method, $endpoint, $data]) {
            $response = $this->json($method, $endpoint, $data);
            $response->assertStatus(401);
        }
    }

    public function test_register_with_duplicate_email_fails()
    {
        // Create a user
        User::factory()->create([
            'email' => 'duplicate@example.com'
        ]);

        // Try to register with same email
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Another User',
            'email' => 'duplicate@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_roles_are_returned_in_validate()
    {
        // Create the admin role
        Role::create(['name' => 'admin', 'guard_name' => 'api']);
        
        // Create a user
        $user = User::factory()->create([
            'email' => 'roletest@example.com',
            'password' => bcrypt('password123')
        ]);

        // Assign a role to the user
        $user->assignRole('admin');

        // Login
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'roletest@example.com',
            'password' => 'password123'
        ]);

        $accessToken = $loginResponse->json('access_token');

        // Validate and check roles
        $validateResponse = $this->postJson('/api/auth/validate', [], [
            'Authorization' => 'Bearer ' . $accessToken
        ]);

        $validateResponse->assertStatus(200)
            ->assertJson([
                'email' => 'roletest@example.com',
                'roles' => ['admin']
            ]);
    }

    public function test_jwt_token_contains_correct_claims()
    {
        // Create and login user
        $user = User::factory()->create([
            'email' => 'jwttest@example.com',
            'password' => bcrypt('password123')
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'jwttest@example.com',
            'password' => 'password123'
        ]);

        $accessToken = $loginResponse->json('access_token');
        $this->assertNotNull($accessToken);

        // Decode JWT token manually to verify claims
        $parts = explode('.', $accessToken);
        $this->assertCount(3, $parts);

        $payload = json_decode(base64_decode($parts[1]), true);
        
        // Verify standard JWT claims
        $this->assertArrayHasKey('iss', $payload);
        $this->assertArrayHasKey('iat', $payload);
        $this->assertArrayHasKey('exp', $payload);
        $this->assertArrayHasKey('sub', $payload);

        // Verify subject is the user's UUID
        $this->assertEquals($user->getKey(), $payload['sub']);
    }
}