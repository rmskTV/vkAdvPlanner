<?php

namespace App\Http\Controllers;

use App\Services\VkAdsAuthService;
use Illuminate\Http\Request;

class HealtCheckController extends Controller
{
    public function __invoke(Request $request, VkAdsAuthService $service): \Illuminate\Http\JsonResponse
    {
        return response()->json($service->getAccessToken(), 201);
    }
}
