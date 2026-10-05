<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

/**
 * Simple file-based sliding-window rate limiter.
 *
 * Stores attempt timestamps in a JSON file per key and uses flock() for an
 * atomic read-modify-write, so it is safe under concurrent requests on a
 * single host.
 *
 * Note: for a multi-server deployment this should be replaced by a shared
 * store (Redis or a database table). The public API is intentionally small
 * so the implementation can be swapped without touching callers.
 */
final class RateLimiter
{
    /**
     * @param string $storageDir   Directory where counter files are stored.
     * @param int    $maxAttempts  Maximum number of attempts per window.
     * @param int    $windowSeconds Sliding window length in seconds.
     */
    public function __construct(
        private readonly string $storageDir,
        private readonly int $maxAttempts,
        private readonly int $windowSeconds,
    ) {
        if ($this->maxAttempts <= 0) {
            throw new InvalidArgumentException('maxAttempts must be a positive integer.');
        }

        if ($this->windowSeconds <= 0) {
            throw new InvalidArgumentException('windowSeconds must be a positive integer.');
        }
    }

    /**
     * Register an attempt for the given key and report whether it is allowed.
     *
     * @return array{allowed: bool, remaining: int, retry_after: int}
     */
    public function hit(string $key): array
    {
        $now  = time();
        $file = $this->fileFor($key);

        $handle = @fopen($file, 'c+');

        // Fail open: if the counter cannot be tracked, do not block traffic.
        if ($handle === false) {
            return ['allowed' => true, 'remaining' => $this->maxAttempts, 'retry_after' => 0];
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return ['allowed' => true, 'remaining' => $this->maxAttempts, 'retry_after' => 0];
            }

            $contents   = (string) stream_get_contents($handle);
            $timestamps = $this->decode($contents);

            // Keep only the attempts that fall inside the current window.
            $threshold  = $now - $this->windowSeconds;
            $timestamps = array_values(array_filter(
                $timestamps,
                static fn (int $timestamp): bool => $timestamp > $threshold
            ));

            if (count($timestamps) >= $this->maxAttempts) {
                $oldest     = min($timestamps);
                $retryAfter = max(1, ($oldest + $this->windowSeconds) - $now);

                return ['allowed' => false, 'remaining' => 0, 'retry_after' => $retryAfter];
            }

            $timestamps[] = $now;

            // Rewrite the counter file atomically.
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, (string) json_encode($timestamps));
            fflush($handle);

            return [
                'allowed'     => true,
                'remaining'   => $this->maxAttempts - count($timestamps),
                'retry_after' => 0,
            ];
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * Reset the counter for a key (e.g. after a successful login).
     */
    public function clear(string $key): void
    {
        $file = $this->fileFor($key);

        if (is_file($file)) {
            @unlink($file);
        }
    }

    /**
     * Resolve the counter file path for a key, creating the directory if needed.
     */
    private function fileFor(string $key): string
    {
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0770, true);
        }

        return $this->storageDir . '/' . hash('sha256', $key) . '.json';
    }

    /**
     * Decode the stored timestamps, ignoring any malformed content.
     *
     * @return list<int>
     */
    private function decode(string $contents): array
    {
        if ($contents === '') {
            return [];
        }

        $data = json_decode($contents, true);

        if (!is_array($data)) {
            return [];
        }

        return array_values(array_filter($data, static fn (mixed $value): bool => is_int($value)));
    }
}
