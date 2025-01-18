<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Traits\ApiResponseHelper;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use ApiResponseHelper;

    public function __construct(protected readonly AuthService $authService) {}

    public function me(Request $request)
    {
        $user = $request->attributes->get('user');
        return $this->successResponse($user);
    }
}
