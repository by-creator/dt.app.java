<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PusherNotifier
{
    public function trigger(string|array $channels, string $event, array $payload): void
    {
        $config = config('services.pusher');

        if (
            blank($config['app_id'] ?? null)
            || blank($config['key'] ?? null)
            || blank($config['secret'] ?? null)
            || blank($config['cluster'] ?? null)
        ) {
            return;
        }

        $channels = array_values(array_filter((array) $channels));

        if ($channels === []) {
            return;
        }

        $body = [
            'name' => $event,
            'channels' => $channels,
            'data' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];

        $path = '/apps/'.$config['app_id'].'/events';
        $bodyJson = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $bodyMd5 = md5($bodyJson);
        $timestamp = time();

        $query = [
            'auth_key' => $config['key'],
            'auth_timestamp' => $timestamp,
            'auth_version' => '1.0',
            'body_md5' => $bodyMd5,
        ];

        ksort($query);
        $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        $stringToSign = "POST\n{$path}\n{$queryString}";
        $signature = hash_hmac('sha256', $stringToSign, $config['secret']);
        $baseUrl = sprintf(
            '%s://api-%s.pusher.com',
            $config['scheme'] ?? 'https',
            $config['cluster']
        );

        try {
            $response = Http::withBody($bodyJson, 'application/json')
                ->timeout(5)
                ->post($baseUrl.$path.'?'.$queryString.'&auth_signature='.$signature);

            if ($response->failed()) {
                Log::warning('Pusher notification rejected', [
                    'event' => $event,
                    'channels' => $channels,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $exception) {
            Log::warning('Pusher notification failed', [
                'event' => $event,
                'channels' => $channels,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
