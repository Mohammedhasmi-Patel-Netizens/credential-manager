<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

trait ApiResponses
{
    /*
    |--------------------------------------------------------------------------
    | Core Response Methods
    |--------------------------------------------------------------------------
    | These are the base methods used by all other specific response types.
    */

    /**
     * Base method for generating a successful JSON response.
     * * @param mixed $data The actual payload (user, post, list, etc.) to return.
     * @param string $message A human-readable message (e.g., "User created successfully").
     * @param int $statusCode HTTP Status code (default: 200).
     * @return JsonResponse
     */
    public function successResponse(mixed $data, string $message = 'Success', int $statusCode = Response::HTTP_OK): JsonResponse
    {
        $response = [
            'status' => true,
            'message' => $message,
            'data' => $data,
        ];

        // Adjust 'status' key based on route type (API vs Web) if needed
        if (request()->attributes->get('route') === 'web') {
            $response['status'] = 'success';
        }

        return new JsonResponse($response, $statusCode);
    }

    /**
     * Base method for generating an error JSON response.
     * * @param mixed $errors Detailed error data (e.g., validation messages, exception trace).
     * @param string $message A summary error message (e.g., "Invalid Credentials").
     * @param int $statusCode HTTP Status code (default: 500).
     * @return JsonResponse
     */
    public function errorResponse(mixed $errors = [], string $message = '', int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR): JsonResponse
    {
        if (!$message) {
            $message = Response::$statusTexts[$statusCode] ?? 'Error';
        }

        $response = [
            'status' => false,
            'message' => $message,
            // 'errors' => $errors,
        ];

        if (request()->attributes->get('route') === 'web') {
            $response['status'] = 'error';
        }

        return new JsonResponse($response, $statusCode);
    }

    /*
    |--------------------------------------------------------------------------
    | 2xx Success Responses
    |--------------------------------------------------------------------------
    */

    /**
     * HTTP 200 OK
     * Use this for standard successful GET, PUT, or PATCH requests.
     * * @param mixed $data The resource data to return.
     * @param string $message Optional success message.
     */
    public function okResponse(mixed $data, string $message = 'OK'): JsonResponse
    {
        return $this->successResponse($data, $message, Response::HTTP_OK);
    }

    /**
     * HTTP 201 Created
     * Use this after successfully creating a new resource (POST).
     * * @param mixed $data The newly created resource.
     * @param string $message Optional success message.
     */
    public function createdResponse(mixed $data, string $message = 'Created'): JsonResponse
    {
        return $this->successResponse($data, $message, Response::HTTP_CREATED);
    }

    /**
     * HTTP 204 No Content
     * Use this when an action is successful but there is nothing to return
     * (e.g., after deleting a record).
     * * Note: This response has NO body.
     */
    public function noContentResponse(): JsonResponse
    {
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /*
    |--------------------------------------------------------------------------
    | 4xx Client Error Responses
    |--------------------------------------------------------------------------
    */

    /**
     * HTTP 400 Bad Request
     * Use this when the server cannot process the request due to a client error
     * (e.g., malformed syntax, invalid parameters).
     */
    public function badRequestResponse(string $message = 'Bad Request', mixed $errors = []): JsonResponse
    {
        return $this->errorResponse($errors, $message, Response::HTTP_BAD_REQUEST);
    }

    /**
     * HTTP 401 Unauthorized
     * Use this when authentication is required and has failed or has not been provided.
     * (e.g., User is not logged in or Token is invalid).
     */
    public function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse([], $message, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * HTTP 403 Forbidden
     * Use this when the user is authenticated but does not have permission to access the resource.
     * (e.g., A Standard User trying to access Admin settings).
     */
    public function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse([], $message, Response::HTTP_FORBIDDEN);
    }

    /**
     * HTTP 404 Not Found
     * Use this when the requested resource could not be found.
     * (e.g., User ID 999 does not exist).
     */
    public function notFoundResponse(string $message = 'Resource Not Found'): JsonResponse
    {
        return $this->errorResponse([], $message, Response::HTTP_NOT_FOUND);
    }

    /**
     * HTTP 405 Method Not Allowed
     * Use this when the HTTP method is known but not supported by the target resource.
     * (e.g., Trying to POST to a read-only endpoint).
     */
    public function methodNotAllowedResponse(string $message = 'Method Not Allowed'): JsonResponse
    {
        return $this->errorResponse([], $message, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * HTTP 409 Conflict
     * Use this when the request conflicts with the current state of the server.
     * (e.g., Trying to register an email that already exists, or editing a deleted record).
     */
    public function conflictResponse(string $message = 'Conflict', mixed $errors = []): JsonResponse
    {
        return $this->errorResponse($errors, $message, Response::HTTP_CONFLICT);
    }

    /**
     * HTTP 422 Unprocessable Entity
     * Use this for Validation Errors. The request was well-formed but unable to be followed
     * due to semantic errors.
     */
    public function unprocessableResponse(mixed $errors, string $message = 'Unprocessable Entity'): JsonResponse
    {
        return $this->errorResponse($errors, $message, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * HTTP 429 Too Many Requests
     * Use this when the user has sent too many requests in a given amount of time ("rate limiting").
     */
    public function tooManyRequestsResponse(string $message = 'Too Many Requests'): JsonResponse
    {
        return $this->errorResponse([], $message, Response::HTTP_TOO_MANY_REQUESTS);
    }

    /*
    |--------------------------------------------------------------------------
    | 5xx Server Error Responses
    |--------------------------------------------------------------------------
    */

    /**
     * HTTP 500 Internal Server Error
     * Use this for generic server errors or unexpected exceptions.
     * * @param mixed $trace Optional stack trace (should only be shown in debug mode).
     */
    public function serverErrorResponse(string $message = 'Internal Server Error', mixed $trace = []): JsonResponse
    {
        $data = app()->environment('local') ? $trace : [];
        return $this->errorResponse($data, $message, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * HTTP 503 Service Unavailable
     * Use this when the server is down for maintenance or overloaded.
     */
    public function serviceUnavailableResponse(string $message = 'Service Unavailable'): JsonResponse
    {
        return $this->errorResponse([], $message, Response::HTTP_SERVICE_UNAVAILABLE);
    }

    /*
    |--------------------------------------------------------------------------
    | Exception Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Throw Validation Exception (422)
     * * Use this to immediately stop execution and throw a validation error.
     * This works inside Controllers, Services, or even Models.
     * * Example: $this->throwValidationError('Invalid Coupon', ['code' => 'Code expired']);
     */
    public function throwValidationError(string $message, mixed $errors = [])
    {
        $response = $this->unprocessableResponse($errors, $message);
        throw new HttpResponseException($response);
    }

    /**
     * Response with status code 422 for only Request class in web routes.
     */
    public function customErrorWeb($message, $httpStatusCode = Response::HTTP_UNPROCESSABLE_ENTITY)
    {
        $response = response()->json([
            'status' => 'validation_error',
            'message' => $message,
        ], $httpStatusCode);
        $validator = Validator::make([], []);
        throw new ValidationException($validator, $response);
    }

    /**
     * Response with status code 422 for only Request class in API routes.
     */
    public function customErrorAPI($message, $httpStatusCode = Response::HTTP_UNPROCESSABLE_ENTITY)
    {
        $response = $this->errorResponse([], $message, $httpStatusCode);
        throw new HttpResponseException($response);
    }
}
