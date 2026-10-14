<?php

namespace App\Exceptions;

use Illuminate\Http\Exceptions\HttpResponseException;

class ApiException extends HttpResponseException
{
    /**
     * Create a new HTTP response exception instance.
     */
    public function __construct($message, $status)
    {
        $response = response()->json(['message' => $message], $status);

        parent::__construct($response);
    }
}
