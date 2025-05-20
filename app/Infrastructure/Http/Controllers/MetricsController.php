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
}