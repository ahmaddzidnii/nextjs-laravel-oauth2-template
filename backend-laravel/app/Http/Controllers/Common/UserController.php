<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Repositories\UserRepository;
use App\Traits\ApiResponseHelper;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponseHelper;

    public function __construct(
        protected readonly UserRepository $userRepository
    ) {}

    public function users(Request $request)
    {
        $userId = $request->attributes->get('user')->sub;
        $user = $this->userRepository->findById($userId);

        return $this->successResponse(UserResource::make($user));
    }
}
