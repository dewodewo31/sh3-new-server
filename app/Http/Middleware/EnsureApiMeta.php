<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnsureApiMeta
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-Id') ?: (string) Str::uuid();

        $request->attributes->set('request_id', $requestId);

        try {
            $response = $next($request);
        } catch (Throwable $e) {
            report($e);

            $response = app(ExceptionHandler::class)->render($request, $e);
        }

        $response->headers->set('X-Request-Id', $requestId);

        if ($response instanceof JsonResponse && $request->is('api/*')) {
            $this->addMeta($response, $requestId);
        }

        return $response;
    }

    private function addMeta(JsonResponse $response, string $requestId): void
    {
        $payload = $response->getData(true);

        if (! is_array($payload)) {
            return;
        }

        $payload['meta'] = [
            'timestamp' => now()->toISOString(),
            'request_id' => $requestId,
        ];

        $response->setData($payload);
    }
}
