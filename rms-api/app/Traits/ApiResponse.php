<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponse
{
    protected function successResponse(
        mixed $data = null,
        string $message = 'Request completed successfully.',
        int $statusCode = Response::HTTP_OK,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json(
            $response,
            $statusCode
        );
    }

    protected function errorResponse(
        string $message = 'Something went wrong.',
        int $statusCode = Response::HTTP_BAD_REQUEST,
        mixed $errors = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json(
            $response,
            $statusCode
        );
    }

    protected function createdResponse(
        mixed $data,
        string $message = 'Resource created successfully.'
    ): JsonResponse {
        return $this->successResponse(
            data: $data,
            message: $message,
            statusCode: Response::HTTP_CREATED
        );
    }

    protected function updatedResponse(
        mixed $data,
        string $message = 'Resource updated successfully.'
    ): JsonResponse {
        return $this->successResponse(
            data: $data,
            message: $message,
            statusCode: Response::HTTP_OK
        );
    }

    protected function deletedResponse(
        string $message = 'Resource deleted successfully.'
    ): JsonResponse {
        return $this->successResponse(
            data: null,
            message: $message,
            statusCode: Response::HTTP_OK
        );
    }

    protected function paginatedResponse(
        mixed $resourceCollection,
        mixed $paginator,
        string $message = 'Data retrieved successfully.'
    ): JsonResponse {
        return $this->successResponse(
            data: $resourceCollection,
            message: $message,
            statusCode: Response::HTTP_OK,
            meta: [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
                'has_more_pages' => $paginator->hasMorePages(),
            ]
        );
    }

    protected function notFoundResponse(
        string $message = 'Resource not found.'
    ): JsonResponse {
        return $this->errorResponse(
            message: $message,
            statusCode: Response::HTTP_NOT_FOUND
        );
    }

    protected function forbiddenResponse(
        string $message = 'You are not authorized to perform this action.'
    ): JsonResponse {
        return $this->errorResponse(
            message: $message,
            statusCode: Response::HTTP_FORBIDDEN
        );
    }
}