<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('assets.symbol');

        return response()->json([
            'balance' => $user->balance,
            'assets' => $user->assets->map(fn (Asset $asset) => [
                'symbol' => $asset->symbol->code,
                'amount' => $asset->amount,
                'locked_amount' => $asset->locked_amount,
            ]),
        ]);
    }
}
