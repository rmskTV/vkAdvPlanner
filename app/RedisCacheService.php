<?php

namespace App;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class RedisCacheService
{
    /**
     * @param  string  $key  Ключ записи кэша
     * @param  mixed  $value  Значение записи кэша
     * @param  string[]|null  $tags  Тэги записи кэша
     * @param  int|null  $expiration  Срок годности кэша в секундах
     */
    public function set(string $key, mixed $value, ?array $tags = [], ?int $expiration = null): void
    {
        Log::info("Setting cache key: {$key} for {$value}");

        if ($expiration) {
            Cache::put($key, $value, $expiration);
        } else {
            Cache::forever($key, $value);
        }
    }

    public function get(string $key): mixed
    {
        $value = Cache::get($key);
        Log::info("Getting cache key: {$key}, value: ".json_encode($value));

        return $value;
    }

    public function forget(string $key): void
    {
        Log::info("Forgetting cache key: {$key}");
        Cache::forget($key);
    }

    public function exists(string $key): bool
    {
        $exists = Cache::has($key);

        Log::info("Checking existence of cache key: {$key}, exists: ".($exists ? 'true' : 'false'));

        return $exists;
    }

    public function create(string $key, mixed $value, ?int $expiration = null): void
    {
        $this->set($key, $value, [], $expiration);
    }

    public function read(string $key): mixed
    {
        return $this->get($key);
    }

    public function update(string $key, mixed $value, ?int $expiration = null): void
    {
        if ($this->exists($key)) {
            $this->set($key, $value, [], $expiration);
        }
    }

    public function delete(string $key): void
    {
        $this->forget($key);
    }

    /**
     * @param  string  $key_name
     */
    public function forgetBySubstring($key_name): void
    {
        $redis = Redis::connection(); // Получаем экземпляр Redis
        $keys = $redis->keys("*{$key_name}*"); // Получаем ключи по подстроке

        if (! empty($keys)) {
            foreach ($keys as $key) {
                Log::info("Delete finded by substring: {$key}");
                $redis->del($key); // Удаляем каждый ключ
            }
        }

    }
}
