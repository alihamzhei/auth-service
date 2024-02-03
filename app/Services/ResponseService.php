<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ResponseService
{
    private mixed $data = [];
    private array $errors = [];
    private string $messages = '';

    /**
     * message
     *
     * @param mixed $data
     * @return ResponseService
     */
    public function data(mixed $data = []): ResponseService
    {
        $this->data = $data;

        return $this;
    }

    /**
     * errors
     *
     * @param array|null $errors
     * @return ResponseService
     */
    public function errors(?array $errors = []): ResponseService
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * message
     *
     * @param string $message
     * @return ResponseService
     */
    public function message(string $message): ResponseService
    {
        $this->messages = __($message);

        return $this;
    }

    /**
     * send
     *
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(int $statusCode = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'message' => $this->messages,
            'errors' => $this->errors,
            'data' => $this->data,
        ], $statusCode);
    }
}
