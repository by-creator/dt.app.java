<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ScanTokenGfaService
{
    private const PREFIX = 'gfa_scan_token:';

    public function generateToken(): string
    {
        $token = Str::random(48);
        Cache::put($this->key($token), true, now()->addMinutes(10));

        return $token;
    }

    public function isValid(?string $token): bool
    {
        if (! $token) {
            return false;
        }

        return Cache::has($this->key($token));
    }

    public function useToken(?string $token): bool
    {
        if (! $this->isValid($token)) {
            return false;
        }

        Cache::forget($this->key($token));

        return true;
    }

    private function key(string $token): string
    {
        return self::PREFIX.$token;
    }
}
