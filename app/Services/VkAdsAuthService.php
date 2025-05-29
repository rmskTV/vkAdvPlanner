<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VkAdsAuthService
{
    protected string $clientId;

    protected string $clientSecret;

    protected string $baseUrl = 'https://ads.vk.com/api/v2/oauth2';

    protected ?string $accessToken = null;

    protected ?string $refreshToken = null;

    protected ?int $expiresAt = null;

    public function __construct()
    {
        $this->clientId = config('services.vk_ads.client_id');
        $this->clientSecret = config('services.vk_ads.client_secret');
        $this->loadToken();
    }

    /**
     * Получение текущего access token
     * При необходимости обновляет его
     */
    public function getAccessToken(): string
    {
        if ($this->isTokenExpired() || ! $this->accessToken) {
            $this->refreshToken();
        }

        return $this->accessToken;
    }

    /**
     * Получение нового access token
     */
    private function requestNewToken(): void
    {
        $response = Http::asForm()->post("{$this->baseUrl}/token.json", [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        $this->handleTokenResponse($response);
    }

    /**
     * Обновление access token
     */
    private function refreshToken(): void
    {
        if (! $this->refreshToken) {
            $this->requestNewToken();

            return;
        }

        try {
            $response = Http::asForm()->post("{$this->baseUrl}/token.json", [
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->refreshToken,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            $this->handleTokenResponse($response);
        } catch (\Exception $e) {
            Log::error('VK Ads token refresh failed', ['error' => $e->getMessage()]);
            $this->requestNewToken();
        }
    }

    /**
     * Удаление всех токенов
     */
    private function revokeAllTokens(): void
    {
        $response = Http::asForm()->post("{$this->baseUrl}/token/delete.json", [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if ($response->successful()) {
            $this->clearTokens();

            return;
        }

        Log::error('VK Ads token revocation failed', ['response' => $response->json()]);

    }

    /**
     * Обработка ответа с токеном
     *
     * @throws \Exception
     */
    protected function handleTokenResponse($response): void
    {
        if ($response->status() === 403) {
            $this->revokeAllTokens();
            $this->requestNewToken();

            return;
        }

        if (! $response->successful()) {
            $error = $response->json();
            throw new \Exception("VK Ads auth error: {$error['message']} ({$error['code']})");
        }

        $data = $response->json();

        $this->accessToken = $data['access_token'];
        $this->refreshToken = $data['refresh_token'] ?? $this->refreshToken;
        $this->expiresAt = now()->addSeconds($data['expires_in'])->timestamp;

        $this->saveToken();
    }

    /**
     * Проверка истечения срока действия токена
     */
    protected function isTokenExpired(): bool
    {
        return $this->expiresAt && $this->expiresAt < now()->timestamp;
    }

    /**
     * Загрузка токена из хранилища (кеш/БД)
     */
    protected function loadToken(): void
    {
        $tokenData = cache()->get('vk_ads_token');

        if ($tokenData) {
            $this->accessToken = $tokenData['access_token'];
            $this->refreshToken = $tokenData['refresh_token'];
            $this->expiresAt = $tokenData['expires_at'];
        }
    }

    /**
     * Сохранение токена в хранилище
     */
    protected function saveToken(): void
    {
        $tokenData = [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'expires_at' => $this->expiresAt,
        ];

        cache()->put('vk_ads_token', $tokenData, $this->expiresAt - now()->timestamp);
    }

    /**
     * Очистка токенов
     */
    protected function clearTokens(): void
    {
        $this->accessToken = null;
        $this->refreshToken = null;
        $this->expiresAt = null;

        cache()->forget('vk_ads_token');
    }
}
