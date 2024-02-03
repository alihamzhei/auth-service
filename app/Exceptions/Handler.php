<?php

namespace App\Exceptions;

use App\Facades\Response;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response as ResponseMain;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register()
    {
    }

    public function render($request, Throwable $e)
    {
        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
            $message = ResponseMain::$statusTexts[$statusCode];

            return Response::message($message)
                ->send($statusCode);
        }

        if ($e instanceof ModelNotFoundException) {
            return Response::message('global.errors.not_found')
                ->send(ResponseMain::HTTP_NOT_FOUND);
        }

        if ($e instanceof BadRequestException) {
            return Response::message($e->getMessage())
                ->send(ResponseMain::HTTP_BAD_REQUEST);
        }

        if ($e instanceof AuthorizationException) {
            return Response::message($e->getMessage())
                ->send(ResponseMain::HTTP_FORBIDDEN);
        }

        if ($e instanceof UnauthorizedException) {
            return Response::message($e->getMessage())
                ->send(ResponseMain::HTTP_FORBIDDEN);
        }

        if ($e instanceof AuthenticationException) {
            return Response::message($e->getMessage())
                ->send(ResponseMain::HTTP_UNAUTHORIZED);
        }

        if ($e instanceof ValidationException) {
            $errors = $e->validator->errors()->messages();

            return Response::errors($errors)
                ->message($e->getMessage())
                ->send(ResponseMain::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($e instanceof ClientException) {
            $errors = $e->getResponse()->getBody();
            $code = $e->getCode();

            return Response::errors($errors)
                ->send($code);
        }

        if (env('APP_DEBUG', false)) {
            return parent::render($request, $e);
        }

        return Response::message('Unexpected Error , try later please')
            ->send(ResponseMain::HTTP_INTERNAL_SERVER_ERROR);
    }
}
