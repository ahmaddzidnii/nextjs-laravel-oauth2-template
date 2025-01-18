<?php

namespace App\Http\Middleware;

use App\Exceptions\AuthException;
use App\Helpers\JwtHelpers;
use App\Repositories\TokenRepository;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthMiddleware
{

    public function __construct(
        protected readonly JwtHelpers $jwtHelpers,
        protected readonly TokenRepository $tokenRepository
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        // Get refresh token from cookie, bearer token, or query string
        $accsessToken = $request->cookie('refresh_token') ?? $request->bearerToken() ?? $request->query('access_token');

        if (!$accsessToken) {
            throw new AuthException();
        }

        try {
            $validatedToken = $this->jwtHelpers->validateToken($accsessToken);
        } catch (\Exception $e) {
            throw new AuthException($e->getMessage());
        }

        // Check if token is blacklisted
        if ($this->tokenRepository->isTokenBlacklisted($accsessToken)) {
            throw new AuthException();
        }

        $request->attributes->add(['user' => $validatedToken['decoded']]);

        return $next($request);
    }
}
