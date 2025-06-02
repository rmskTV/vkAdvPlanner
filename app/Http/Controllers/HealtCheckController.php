<?php

namespace App\Http\Controllers;

use App\Services\VkAds\VkAdsAgencyClientsService;
use App\Services\VkAds\VkAdsAuthService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;

class HealtCheckController extends Controller
{
    /**
     * @throws ConnectionException
     */
    public function __invoke(
        Request $request,
        VkAdsAuthService $service,
        VkAdsAgencyClientsService $clienstService): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            [
                'token_length' => mb_strlen($service->getAccessToken()),
                'clients_length' => $clienstService->listClients(),
            ], 200);
    }
}
