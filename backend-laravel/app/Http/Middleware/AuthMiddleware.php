<?php

namespace App\Http\Middleware;

use App\Helpers\JwtHelpers;
use App\Traits\ApiResponseHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthMiddleware
{
    protected $jwtHelpers;

    public function __construct(JwtHelpers $jwtHelpers)
    {
        $this->jwtHelpers = $jwtHelpers;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Get refresh token from cookie, bearer token, or query string
            $refreshToken = $request->cookie('refresh_token') ?? $request->bearerToken() ?? $request->query('refresh_token');

            if (!$refreshToken) {
                throw new \Exception("Token is not given", 401);
            }

            $validatedToken = $this->jwtHelpers->validateToken($refreshToken);
            $request->attributes->add(['user' => $validatedToken['decoded']]);
            return $next($request);
        } catch (\Exception $e) {
            return (new class {
                use ApiResponseHelper;
            })->errorResponse($e->getMessage(), $e->getCode());
        }
    }
}
