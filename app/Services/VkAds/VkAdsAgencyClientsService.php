<?php

namespace App\Services\VkAds;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VkAdsAgencyClientsService
{
    private const BASE_URL = 'https://ads.vk.com/api/v2/agency/clients';

    private VkAdsAuthService $authService;

    public function __construct(VkAdsAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Получить список клиентов агентства
     * @throws ConnectionException
     */
    public function listClients(array $params = []): array
    {
        $response = $this->makeAuthenticatedRequest()
            ->get(self::BASE_URL.'.json', $params);

        return $this->handleResponse($response);
    }

    /**
     * Создать нового клиента или добавить существующего
     * @throws ConnectionException
     */
    public function createClient(array $data): array
    {
        $response = $this->makeAuthenticatedRequest()
            ->post(self::BASE_URL.'.json', $data);

        return $this->handleResponse($response);
    }

    /**
     * Обновить данные клиента
     * @throws ConnectionException
     */
    public function updateClient(int $clientId, array $data): array
    {
        $response = $this->makeAuthenticatedRequest()
            ->post(self::BASE_URL."/{$clientId}.json", $data);

        return $this->handleResponse($response);
    }

    /**
     * Удалить клиента из агентства
     * @throws ConnectionException
     */
    public function deleteClient(int $clientId): array
    {
        $response = $this->makeAuthenticatedRequest()
            ->delete(self::BASE_URL."/{$clientId}.json");

        return $this->handleResponse($response);
    }

    /**
     * Создать авторизованный HTTP-клиент
     */
    private function makeAuthenticatedRequest()
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.$this->authService->getAccessToken(),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Обработка ответа API
     */
    private function handleResponse(Response $response): array
    {
        $status = $response->status();
        $data = $response->json();

        if ($status === 204) {
            return ['success' => true];
        }

        if ($response->successful()) {
            return $data;
        }

        $error = [
            'status' => $status,
            'error' => $data['error'] ?? ['code' => 'unknown', 'message' => 'Unknown error'],
        ];

        Log::error('VK Ads API error', $error);

        throw new \RuntimeException(
            $error['error']['message'] ?? 'Unknown VK Ads API error',
            $status
        );
    }

    /**
     * Поиск клиентов по имени
     * @throws ConnectionException
     */
    public function searchClients(string $query, int $limit = 20): array
    {
        return $this->listClients([
            '_q' => $query,
            'limit' => $limit,
        ]);
    }

    /**
     * Получить клиента по ID
     * @throws ConnectionException
     */
    public function getClient(int $clientId): ?array
    {
        $response = $this->listClients(['_user__id' => $clientId]);

        return $response['items'][0] ?? null;
    }
}
