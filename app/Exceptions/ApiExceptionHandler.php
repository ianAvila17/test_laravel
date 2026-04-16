<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiExceptionHandler
{
    public static function register(Exceptions $exceptions): void
    {
        $handler = new self;

        $exceptions->shouldRenderJsonWhen(function ($request) use ($handler): bool {
            return $handler->shouldRenderJson($request);
        });

        $exceptions->render(function (ValidationException $exception, $request) use ($handler) {
            return $handler->renderValidation($exception, $request);
        });

        $exceptions->render(function (ModelNotFoundException $exception, $request) use ($handler) {
            return $handler->renderModelNotFound($exception, $request);
        });

        $exceptions->render(function (NotFoundHttpException $exception, $request) use ($handler) {
            return $handler->renderNotFoundHttp($exception, $request);
        });

        $exceptions->render(function (\Throwable $exception, $request) use ($handler) {
            return $handler->renderThrowable($exception, $request);
        });
    }

    public function shouldRenderJson(Request $request): bool
    {
        return $request->is('api/*');
    }

    public function renderValidation(ValidationException $exception, Request $request): ?JsonResponse
    {
        if (! $this->shouldRenderJson($request)) {
            return null;
        }

        return response()->json([
            'message' => 'Validation failed.',
            'errors' => $exception->errors(),
        ], 422);
    }

    public function renderModelNotFound(ModelNotFoundException $exception, Request $request): ?JsonResponse
    {
        if (! $this->shouldRenderJson($request)) {
            return null;
        }

        return response()->json([
            'message' => $this->notFoundMessage($exception->getModel()),
        ], 404);
    }

    public function renderNotFoundHttp(NotFoundHttpException $exception, Request $request): ?JsonResponse
    {
        if (! $this->shouldRenderJson($request)) {
            return null;
        }

        $previous = $exception->getPrevious();
        if ($previous instanceof ModelNotFoundException) {
            return response()->json([
                'message' => $this->notFoundMessage($previous->getModel()),
            ], 404);
        }

        return response()->json([
            'message' => 'Resource not found.',
        ], 404);
    }

    public function renderThrowable(\Throwable $exception, Request $request): ?JsonResponse
    {
        if (! $this->shouldRenderJson($request)) {
            return null;
        }

        return response()->json([
            'message' => 'Unexpected server error.',
        ], 500);
    }

    private function notFoundMessage(?string $modelClass): string
    {
        $resource = class_basename($modelClass ?? 'Resource');

        return "{$resource} not found.";
    }
}
