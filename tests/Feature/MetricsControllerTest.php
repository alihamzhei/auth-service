<?php

namespace Tests\Feature;

use Tests\TestCase;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Mockery;

class MetricsControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a mock for the Prometheus registry
        $this->mockRegistry = Mockery::mock(CollectorRegistry::class);
        $this->app->instance(CollectorRegistry::class, $this->mockRegistry);
    }
    
    public function test_metrics_endpoint_returns_prometheus_metrics()
    {
        // Setup mock response
        $mockSamples = ['sample_metric' => 'sample_value'];
        $this->mockRegistry->shouldReceive('getMetricFamilySamples')->once()->andReturn($mockSamples);
        
        // Make request to metrics endpoint
        $response = $this->get('/api/metrics');
        
        // Assert response is successful and has correct content type
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', RenderTextFormat::MIME_TYPE);
    }
    
    public function test_metrics_endpoint_is_accessible()
    {
        // Setup mock response for this test too
        $mockSamples = ['sample_metric' => 'sample_value'];
        $this->mockRegistry->shouldReceive('getMetricFamilySamples')->once()->andReturn($mockSamples);
        
        // This test verifies the route exists and is accessible
        $response = $this->get('/api/metrics');
        
        $response->assertStatus(200);
    }
    
    public function test_metrics_endpoint_handles_empty_metrics()
    {
        // Setup mock response with empty metrics
        $mockSamples = [];
        $this->mockRegistry->shouldReceive('getMetricFamilySamples')->once()->andReturn($mockSamples);
        
        $response = $this->get('/api/metrics');
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', RenderTextFormat::MIME_TYPE);
        // Empty metrics should still return a valid response
        $this->assertNotNull($response->getContent());
    }
    
    public function test_metrics_endpoint_handles_registry_exception()
    {
        // Setup mock to throw an exception
        $this->mockRegistry->shouldReceive('getMetricFamilySamples')
            ->once()
            ->andThrow(new \Exception('Registry error'));
        
        $response = $this->get('/api/metrics');
        
        // Even with an exception, the endpoint should return a valid response
        // with a server error status code
        $response->assertStatus(500);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}