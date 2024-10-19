<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\LoginRequest;
use App\Http\Resources\API\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends BaseAPIController
{
    public function login(LoginRequest $request)
    {
        $auth = Auth::attempt($request->only('email', 'password'));
        if ($auth) {
            $user = Auth::user();
            return $this->successResponse([
                'user' => new UserResource($user),
                'token' => $user->createToken('PersonalAccessToken')->accessToken,
            ]);
        }else{
            return $this->errorResponse('Authentication Failed', Response::HTTP_UNAUTHORIZED);
        }
    }
}
