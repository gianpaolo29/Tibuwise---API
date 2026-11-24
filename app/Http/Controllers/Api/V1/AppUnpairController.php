<?php

namespace App\Http\Controllers\Api\V1;

use App\Auth\AppTokenGuard;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AppUnpairController extends Controller
{
    public function destroy(): JsonResponse
    {
        /** @var AppTokenGuard $guard */
        $guard = auth('app');
        $appToken = $guard->appToken();

        if ($appToken) {
            $appToken->delete();
        }

        return response()->json(['message' => 'Phone unpaired successfully.']);
    }
}
