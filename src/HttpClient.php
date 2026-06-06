<?php

declare(strict_types=1);

namespace QrCommunication\VivaLocalTerminal;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use QrCommunication\VivaLocalTerminal\Exceptions\ApiException;

/**
 * Low-level HTTP client for the Local Terminal (P2P) API.
 *
 * Transport model:
 *   - Requests go DIRECTLY to the terminal on the LAN (base URL = terminal IP:port).
 *   - NO authentication: no Authorization header, no token exchange. The terminal
 *     trusts any caller on the closed network (per the official spec).
 *   - HTTPS is served by the terminal, often with a self-signed certificate, hence
 *     the configurable `verify` (TLS) option exposed through {@see Config}.
 *
 * Error model:
 *   The API returns JSON on 2xx but a PLAIN STRING body on 400 (e.g.
 *   "ZeroconfException error message Amount cannot be null"). This client decodes
 *   JSON when possible and preserves the raw string under `raw` otherwise.
 */
final class HttpClient
{
    private readonly Client $guzzle;

    public function __construct(
        private readonly Config $config,
        ?Client $guzzle = null,
    ) {
        $this->guzzle = $guzzle ?? new Client([
            'timeout' => $config->timeout,
            'connect_timeout' => $config->connectTimeout,
            'http_errors' => false,
            'verify' => $config->verifyTls,
            'headers' => ['Accept' => 'application/json'],
        ]);
    }

    /**
     * Perform a GET request against a `/pos/v1/...` endpoint on the terminal.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->doRequest('GET', $this->config->url($path), [
            RequestOptions::QUERY => $query,
        ]);
    }

    /**
     * Perform a POST request against a `/pos/v1/...` endpoint on the terminal.
     *
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function post(string $path, array $body = []): array
    {
        return $this->doRequest('POST', $this->config->url($path), [
            RequestOptions::JSON => $body,
        ]);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function doRequest(string $method, string $url, array $options = []): array
    {
        try {
            $response = $this->guzzle->request($method, $url, $options);
        } catch (GuzzleException $e) {
            throw new ApiException(
                "Local Terminal request failed (is the terminal reachable on the LAN?): {$e->getMessage()}",
                0,
                null,
                $e,
            );
        }

        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($body === '' && $statusCode >= 200 && $statusCode < 300) {
            return [];
        }

        $decoded = json_decode($body, true);

        if ($statusCode >= 400) {
            // The Local Terminal API returns a plain string body on 400.
            $message = is_array($decoded)
                ? ($decoded['message'] ?? $decoded['error'] ?? "HTTP {$statusCode}")
                : ($body !== '' ? $body : "HTTP {$statusCode}");

            throw new ApiException(
                $message,
                $statusCode,
                is_array($decoded) ? $decoded : ['raw' => $body],
            );
        }

        return is_array($decoded) ? $decoded : ['raw' => $body];
    }
}
