<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;
use Mockery;
use Mockery\MockInterface;

class HealthControllerTest extends TestCase
{
    public function test_health_check_returns_ok_when_all_services_are_up()
    {
        // Mock the DB and Redis connections to ensure they return successfully
        DB::shouldReceive('connection->getPdo')->once()->andReturn(true);
        Redis::shouldReceive('ping')->once()->andReturn(true);

        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'services' => [
                    'database' => 'ok',
                    'redis' => 'ok'
                ]
            ])
            ->assertJsonStructure([
                'status',
                'timestamp',
                'services' => [
                    'database',
                    'redis'
                ]
            ]);
    }

    public function test_health_check_returns_error_when_database_is_down()
    {
        // Mock DB to throw exception and Redis to succeed
        DB::shouldReceive('connection->getPdo')->once()
            ->andThrow(new \Exception('Database connection failed'));
        Redis::shouldReceive('ping')->once()->andReturn(true);

        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'error',
                'services' => [
                    'database' => 'error',
                    'redis' => 'ok'
                ]
            ]);
    }

    public function test_health_check_returns_error_when_redis_is_down()
    {
        // Mock DB to succeed and Redis to throw exception
        DB::shouldReceive('connection->getPdo')->once()->andReturn(true);
        Redis::shouldReceive('ping')->once()
            ->andThrow(new \Exception('Redis connection failed'));

        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'error',
                'services' => [
                    'database' => 'ok',
                    'redis' => 'error'
                ]
            ]);
    }

    public function test_health_check_returns_error_when_all_services_are_down()
    {
        // Mock both DB and Redis to throw exceptions
        DB::shouldReceive('connection->getPdo')->once()
            ->andThrow(new \Exception('Database connection failed'));
        Redis::shouldReceive('ping')->once()
            ->andThrow(new \Exception('Redis connection failed'));

        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'error',
                'services' => [
                    'database' => 'error',
                    'redis' => 'error'
                ]
            ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}