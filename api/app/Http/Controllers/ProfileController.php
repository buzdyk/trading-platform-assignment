<?php

namespace App\Http\Controllers;

use App\Http\Resources\AssetResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('assets.symbol');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'balance' => $user->balance,
            'assets' => AssetResource::collection($user->assets),
        ]);
    }
}
