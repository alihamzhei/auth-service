<?php

namespace App\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\Controller;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $status = [
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'services' => [
                'database' => 'ok',
                'redis' => 'ok'
            ]
        ];

        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $status['services']['database'] = 'error';
            $status['status'] = 'error';
        }

        try {
            Redis::ping();
        } catch (\Exception $e) {
            $status['services']['redis'] = 'error';
            $status['status'] = 'error';
        }

        return response()->json($status);
    }
}