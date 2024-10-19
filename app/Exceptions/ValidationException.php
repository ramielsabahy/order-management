<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class ValidationException extends Exception
{
    public function render()
    {
        return response()->json([
            'error' => $this->message,
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
