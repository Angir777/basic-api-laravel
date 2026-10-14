<?php

namespace App\Utils\Response;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class ResponseUtils
{
    public static function response(
        mixed $data,
        int $status = Response::HTTP_OK,
        string $message = 'OK'
    ): JsonResponse {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
