<?php

namespace App\Support;

use App\Models\Map;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

class ConditionTimerRateLimiter
{
    /**
     * @param array<int, int> $tokenHits
     * @return array<string, mixed>|null
     */
    public function check(User $user, Map $map, array $tokenHits, int $selectionCount): ?array
    {
        $mapKey = $this->mapKey($user, $map);
        $mapLimit = config('condition_timers.rate_limit.per_map.max_attempts', 45);
        $mapDecay = config('condition_timers.rate_limit.per_map.decay_seconds', 60);

        if ($violation = $this->checkKey($mapKey, $mapLimit, $mapDecay, $selectionCount, ['scope' => 'map', 'map_id' => $map->id])) {
            return $violation;
        }

        $tokenLimit = config('condition_timers.rate_limit.per_token.max_attempts', 12);
        $tokenDecay = config('condition_timers.rate_limit.per_token.decay_seconds', 60);

        foreach ($tokenHits as $tokenId => $hits) {
            $tokenKey = $this->tokenKey($user, (int) $tokenId);

            if ($violation = $this->checkKey(
                $tokenKey,
                $tokenLimit,
                $tokenDecay,
                max(1, $hits) * $selectionCount,
                ['scope' => 'token', 'token_id' => (int) $tokenId]
            )) {
                return $violation;
            }
        }

        return null;
    }

    /**
     * @param array<int, int> $tokenHits
     */
    public function hit(User $user, Map $map, array $tokenHits): void
    {
        $mapKey = $this->mapKey($user, $map);
        $mapDecay = config('condition_timers.rate_limit.per_map.decay_seconds', 60);

        $this->hitKey($mapKey, $mapDecay);
        $this->clearLockout($mapKey);

        $tokenDecay = config('condition_timers.rate_limit.per_token.decay_seconds', 60);

        foreach ($tokenHits as $tokenId => $hits) {
            $tokenKey = $this->tokenKey($user, (int) $tokenId);

            for ($index = 0; $index < max(1, $hits); $index++) {
                $this->hitKey($tokenKey, $tokenDecay);
            }

            $this->clearLockout($tokenKey);
        }
    }

    public function cooldownFor(User $user, Map $map): ?int
    {
        $key = $this->circuitKey($user, $map);

        if (! RateLimiter::tooManyAttempts($key, 1)) {
            return null;
        }

        return RateLimiter::availableIn($key);
    }

    public function triggerCircuit(User $user, Map $map): int
    {
        $key = $this->circuitKey($user, $map);
        $decay = config('condition_timers.circuit_breaker.cooldown_seconds', 120);

        RateLimiter::hit($key, $decay);

        return RateLimiter::availableIn($key);
    }

    public function clear(User $user, Map $map, array $tokenIds = []): void
    {
        $mapKey = $this->mapKey($user, $map);
        RateLimiter::clear($mapKey);
        $this->clearLockout($mapKey);

        foreach ($tokenIds as $tokenId) {
            $tokenKey = $this->tokenKey($user, (int) $tokenId);
            RateLimiter::clear($tokenKey);
            $this->clearLockout($tokenKey);
        }

        RateLimiter::clear($this->circuitKey($user, $map));
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>|null
     */
    protected function checkKey(string $key, int $limit, int $decay, int $selectionCount, array $context): ?array
    {
        if (! RateLimiter::tooManyAttempts($key, $limit)) {
            return null;
        }

        $lockouts = RateLimiter::hit($this->lockoutKey($key), config('condition_timers.rate_limit.lockout_decay_seconds', 300));
        $availableIn = RateLimiter::availableIn($key);
        $suggestedBackoff = $this->calculateBackoff($availableIn, $lockouts, $selectionCount);

        return array_merge($context, [
            'available_in' => $availableIn,
            'suggested_backoff' => $suggestedBackoff,
            'lockouts' => $lockouts,
            'decay' => $decay,
        ]);
    }

    protected function hitKey(string $key, int $decay): void
    {
        RateLimiter::hit($key, $decay);
    }

    protected function clearLockout(string $key): void
    {
        RateLimiter::clear($this->lockoutKey($key));
    }

    protected function calculateBackoff(int $availableIn, int $lockouts, int $selectionCount): int
    {
        $base = max(1, $availableIn);
        $exponent = min(6, $lockouts + (int) floor($selectionCount / 3));

        return min(900, (int) ($base + (2 ** $exponent)));
    }

    protected function mapKey(User $user, Map $map): string
    {
        return sprintf('condition_timer:map:%d:user:%d', $map->id, $user->getAuthIdentifier());
    }

    protected function tokenKey(User $user, int $tokenId): string
    {
        return sprintf('condition_timer:token:%d:user:%d', $tokenId, $user->getAuthIdentifier());
    }

    protected function lockoutKey(string $key): string
    {
        return $key.':lockouts';
    }

    protected function circuitKey(User $user, Map $map): string
    {
        return sprintf('condition_timer:circuit:%d:user:%d', $map->id, $user->getAuthIdentifier());
    }
}
