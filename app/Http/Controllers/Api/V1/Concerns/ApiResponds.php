<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use Illuminate\Http\JsonResponse;

/**
 * Standard response envelope (§49) for every API v1 endpoint: a success
 * payload is always {"data": ...}, optionally with {"meta": ...} for
 * pagination. Error responses are left to Laravel's default JSON exception
 * rendering (enabled for /api/* in bootstrap/app.php) — {"message": ...}
 * with {"errors": {...}} on validation failures — which is already the
 * shape most API consumers expect from a Laravel backend.
 */
trait ApiResponds
{
    protected function ok(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json(['data' => $data], $status);
    }

    protected function created(mixed $data): JsonResponse
    {
        return $this->ok($data, 201);
    }

    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
