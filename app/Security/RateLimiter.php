<?php

declare(strict_types=1);

namespace App\Security;

use RuntimeException;

final class RateLimiter
{
    public function __construct(private readonly string $directory)
    {
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Rate limit storage is not writable.');
        }
    }

    public function retryAfter(string $key, int $limit, int $seconds): int
    {
        $file = fopen($this->directory . '/' . hash('sha256', $key) . '.json', 'c+');
        if ($file === false) {
            throw new RuntimeException('Rate limit storage is not writable.');
        }
        if (!flock($file, LOCK_EX)) {
            fclose($file);
            throw new RuntimeException('Rate limit storage is not writable.');
        }

        try {
            $now = time();
            $attempts = json_decode(stream_get_contents($file) ?: '[]', true);
            $attempts = is_array($attempts) ? $attempts : [];
            $attempts = array_values(array_filter($attempts, static fn ($time) => is_int($time) && $time > $now - $seconds));
            if (count($attempts) >= $limit) {
                return max(1, $attempts[0] + $seconds - $now);
            }

            $attempts[] = $now;
            rewind($file);
            ftruncate($file, 0);
            fwrite($file, json_encode($attempts, JSON_THROW_ON_ERROR));
            fflush($file);
            return 0;
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }
    }
}
