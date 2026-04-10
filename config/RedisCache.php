<?php
namespace config;

final class RedisCache {
    public function __construct(private \Redis $redis) {}

    public function getJson(string $key): mixed {
        $val = $this->redis->get($key);
        return $val ? json_decode($val, true) : null;
    }

    public function setJson(string $key, mixed $value, int $ttlSec): void {
        $this->redis->setex($key, $ttlSec, json_encode($value, JSON_UNESCAPED_UNICODE));
    }

    public function del(string $key): void {
        $this->redis->del($key);
    }
}
