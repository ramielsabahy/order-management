<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BaseAPIController extends Controller
{
    public function successResponse($data, $code = 200): JsonResponse
    {
        return response()->json([
            'date'  => $data
        ], $code);
    }

    public function errorResponse($message, $code = 400): JsonResponse
    {
        return response()->json(['error' => $message], $code);
    }
}
