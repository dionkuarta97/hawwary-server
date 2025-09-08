<?php

namespace App\Traits;

trait ApiResponse
{
    public function successResponse($data, $message = 'Berhasil', $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    public function successResponseWithPagination($data, $message = 'Berhasil', $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'metadata' => $data
        ], $code);
    }

    public function errorResponse($message = 'Terjadi kesalahan Pada Server', $code = 500, $errors = null)
    {
        $response = [
            'success' => false,
            'message' => is_array($message) ? $message : [$message]
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}
