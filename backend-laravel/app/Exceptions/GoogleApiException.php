<?php

namespace App\Exceptions;

use Exception;

class GoogleApiException extends Exception
{
    protected $statusCode;

    public function __construct($message, $statusCode = 400)
    {
        parent::__construct($message, $statusCode);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
