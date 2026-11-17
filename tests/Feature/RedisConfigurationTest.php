<?php

namespace Tests\Feature;

use App\Services\RedisQuizService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Predis\Connection\Resource\Exception\StreamInitException;
use Tests\TestCase;

class RedisConfigurationTest extends TestCase
{
    public function test_cache_fails_instead_of_switching_stores_when_redis_is_unavailable(): void
    {
        config([
            'cache.default' => 'redis',
            'database.redis.cache.url' => null,
            'database.redis.cache.host' => '127.0.0.1',
            'database.redis.cache.port' => 1,
            'database.redis.cache.password' => null,
            'database.redis.cache.timeout' => 0.01,
            'database.redis.cache.max_retries' => 0,
        ]);

        Redis::purge('cache');
        Cache::purge('redis');

        $this->expectException(StreamInitException::class);

        Cache::put('redis-only-test-'.Str::uuid(), 'available', 60);
    }

    public function test_quiz_service_handles_unavailable_redis(): void
    {
        config([
            'database.redis.default.url' => null,
            'database.redis.default.host' => '127.0.0.1',
            'database.redis.default.port' => 1,
            'database.redis.default.password' => null,
            'database.redis.default.timeout' => 0.01,
            'database.redis.default.max_retries' => 0,
        ]);

        Redis::purge('default');
        Cache::store('file')->forget('fsr_redis_available_status');
        RedisQuizService::resetAvailability();

        $this->assertFalse(RedisQuizService::isAvailable());
    }
}
