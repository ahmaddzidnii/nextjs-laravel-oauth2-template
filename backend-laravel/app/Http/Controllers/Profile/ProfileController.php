<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Traits\ApiResponseHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    use ApiResponseHelper;

    public function __construct(protected readonly AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function me(Request $request)
    {
        $user = $this->authService->handleGetMe($request);
        return $this->successResponse($user);
    }
}
