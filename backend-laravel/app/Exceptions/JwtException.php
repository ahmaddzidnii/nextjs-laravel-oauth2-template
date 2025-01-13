<?php

namespace App\Exceptions;

use Exception;

class JwtException extends Exception
{
    protected int $statusCode;

    public function __construct(string $message, int $statusCode)
    {
        parent::__construct($message, $statusCode);
    }
}
