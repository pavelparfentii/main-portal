<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class InvalidTokenException extends Exception
{
    public function __construct($message = "Invalid or expired token", $code = 403, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

//    public function render($request, Throwable $exception)
//    {
//        if ($exception instanceof InvalidTokenException) {
//            return response()->json(['error' => $exception->getMessage()], 403);
//        }
//
//        return parent::render($request, $exception);
//    }
}
