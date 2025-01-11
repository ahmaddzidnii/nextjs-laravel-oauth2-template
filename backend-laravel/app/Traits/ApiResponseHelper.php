<?php

namespace App\Traits;

trait ApiResponseHelper
{
    public function successResponse($data, $code = 200, $pagination = null)
    {
        $response = [
            'code' => $code,
            'data' => $data,
        ];

        if ($pagination) {
            $response['pagination'] = $pagination;
        }

        return response()->json($response, $code);
    }

    public function errorResponse($error, $code = 400)
    {
        $response = [
            'code' => $code,
            'error' => $error,
        ];

        return response()->json($response, $code);
    }
}
