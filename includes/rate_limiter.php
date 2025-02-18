<?php
require_once __DIR__ . '/../config/database.php';

class RateLimiter {
    private $redis;
    private $maxRequests = 60; // 1 dakikada maksimum istek sayısı
    private $timeWindow = 60; // 1 dakika

    public function __construct() {
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1', 6379);
    }

    public function checkLimit($identifier) {
        $key = "rate_limit:{$identifier}";
        $current = $this->redis->get($key);

        if (!$current) {
            $this->redis->setex($key, $this->timeWindow, 1);
            return true;
        }

        if ($current >= $this->maxRequests) {
            return false;
        }

        $this->redis->incr($key);
        return true;
    }

    public function getRemainingRequests($identifier) {
        $key = "rate_limit:{$identifier}";
        $current = $this->redis->get($key);
        
        if (!$current) {
            return $this->maxRequests;
        }

        return max(0, $this->maxRequests - $current);
    }

    public function getResetTime($identifier) {
        $key = "rate_limit:{$identifier}";
        return $this->redis->ttl($key);
    }
} 