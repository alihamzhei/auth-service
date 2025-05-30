<?php

namespace App\Infrastructure\Http\Controllers;

use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat; // Add this line

class MetricsController extends Controller
{
    private CollectorRegistry $registry;

    public function __construct(CollectorRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function metrics(): Response
    {
        try {
            // Add some basic auth service metrics
            $this->addAuthMetrics();
            
            $renderer = new RenderTextFormat();
            $result = $renderer->render($this->registry->getMetricFamilySamples());

            return response($result, 200, ['Content-Type' => RenderTextFormat::MIME_TYPE]);
        } catch (\Exception $e) {
            // Log the exception
            logger()->error('Error retrieving metrics: ' . $e->getMessage());

            // Return a 500 error response
            return response('Error retrieving metrics', 500, ['Content-Type' => 'text/plain']);
        }
    }

    private function addAuthMetrics(): void
    {
        // Total users count
        $userCount = \App\Models\User::count();
        $gauge = $this->registry->getOrRegisterGauge('auth_service', 'total_users', 'Total number of registered users');
        $gauge->set($userCount);

        // Service uptime (basic metric)
        $uptimeGauge = $this->registry->getOrRegisterGauge('auth_service', 'uptime_seconds', 'Auth service uptime in seconds');
        $uptimeGauge->set(time() - strtotime('today')); // Simple uptime since start of day
    }
}