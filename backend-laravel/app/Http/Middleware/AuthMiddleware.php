<?php

namespace App\Http\Middleware;

use App\Exceptions\AuthException;
use App\Helpers\JwtHelpers;
use App\Traits\ApiResponseHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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

        // Get refresh token from cookie, bearer token, or query string
        $accsessToken = $request->cookie('refresh_token') ?? $request->bearerToken() ?? $request->query('refresh_token');

        if (!$accsessToken) {
            throw new AuthException();
        }

        $validatedToken = $this->jwtHelpers->validateToken($accsessToken);
        $request->attributes->add(['user' => $validatedToken['decoded']]);
        return $next($request);
    }
}
