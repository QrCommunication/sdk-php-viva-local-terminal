<?php

declare(strict_types=1);

namespace QrCommunication\VivaLocalTerminal;

/**
 * Configuration for the Viva.com Local Terminal API.
 *
 * Unlike the Viva cloud APIs (api.vivapayments.com, OAuth2), the Local Terminal
 * API is a PEER-TO-PEER (P2P) protocol: the client talks DIRECTLY to the EFT POS
 * terminal over the local network. There is therefore:
 *
 *   - NO cloud "environment" (demo/production) — the base URL is the terminal's
 *     own IP address and port, e.g. `https://192.168.1.50:8080`.
 *   - NO authentication. The official spec states verbatim:
 *       "In a closed network environment, authentication is not required for
 *        peer-to-peer communication. [...] eliminating the need to include an
 *        Authorization tag in the header."
 *
 * The terminal serves HTTPS, usually with a self-signed certificate. By default
 * this SDK VERIFIES TLS. On a closed LAN with a self-signed terminal cert you
 * will typically need to either pin the terminal's CA bundle via
 * {@see self::$verifyTls} (a path to a PEM file) or — accepting the LAN-only
 * trust model — disable verification by passing `verifyTls: false`.
 */
final class Config
{
    public readonly string $baseUrl;

    /**
     * @param  string  $terminalBaseUrl  The terminal's base URL: scheme + IP + port,
     *                                    e.g. `https://192.168.1.50:8080`. A trailing
     *                                    slash, if present, is stripped.
     * @param  bool|string  $verifyTls  Guzzle `verify` option. `true` (default) verifies
     *                                  against the system CA bundle; a string is treated as
     *                                  the path to a custom CA/PEM bundle (recommended for a
     *                                  self-signed terminal cert); `false` disables
     *                                  verification (acceptable only on a trusted closed LAN).
     * @param  bool  $useIsvEndpoints  When true, requests target the ISV variants of the
     *                                 endpoints (trailing-slash paths, e.g. `/pos/v1/sale/`).
     *                                 The merchant (default) variants use no trailing slash.
     * @param  int  $timeout  Per-request timeout in seconds. Terminal interactions are
     *                        async (they return PROCESSING immediately), so a short timeout
     *                        is fine for the initiating call.
     * @param  int  $connectTimeout  TCP connect timeout in seconds.
     */
    public function __construct(
        string $terminalBaseUrl,
        public readonly bool|string $verifyTls = true,
        public readonly bool $useIsvEndpoints = false,
        public readonly int $timeout = 30,
        public readonly int $connectTimeout = 10,
    ) {
        $trimmed = rtrim(trim($terminalBaseUrl), '/');

        if ($trimmed === '') {
            throw new \InvalidArgumentException('terminalBaseUrl cannot be empty.');
        }

        if (! preg_match('#^https?://#i', $trimmed)) {
            throw new \InvalidArgumentException(
                'terminalBaseUrl must include the scheme, e.g. "https://192.168.1.50:8080".',
            );
        }

        $this->baseUrl = $trimmed;
    }

    /**
     * Build the absolute URL for a `/pos/v1/...` endpoint.
     *
     * The ISV variants of the endpoints differ only by a trailing slash. When
     * {@see self::$useIsvEndpoints} is true, a trailing slash is appended to the
     * path (unless one is already present), matching the spec's `(ISV)` paths.
     *
     * @param  string  $path  The endpoint path, e.g. `/pos/v1/sale`.
     */
    public function url(string $path): string
    {
        $path = '/'.ltrim($path, '/');

        if ($this->useIsvEndpoints && ! str_ends_with($path, '/')) {
            $path .= '/';
        }

        return $this->baseUrl.$path;
    }
}
