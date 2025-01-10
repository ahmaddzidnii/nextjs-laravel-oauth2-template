<?php

namespace App\Helpers;

class ResponseHelper
{
    /**
     * Success response format.
     *
     * @param string $message
     * @param array $data
     * @param int $code
     * @return array
     */
    public static function success($message, $data = [], $code = 200)
    {
        return [
            'status_code' => $code,
            'message' => $message,
            'data' => $data,
        ];
    }

    /**
     * Error response format.
     *
     * @param string $message
     * @param array $errors
     * @param int $code
     * @return array
     */
    public static function error($message, $errors = [], $code = 400)
    {
        return [
            'status_code' => $code,
            'message' => $message,
            'errors' => $errors,
        ];
    }
}
