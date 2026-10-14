<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\User\UserResource;
use App\Utils\Response\ResponseUtils;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class CurrentUserController extends Controller
{
    public function getCurrentUser(Request $request): JsonResponse
    {
        return ResponseUtils::response(data: new UserResource(Auth::user()));
    }
}
