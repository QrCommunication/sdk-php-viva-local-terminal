<?php

declare(strict_types=1);

namespace QrCommunication\VivaLocalTerminal\Resources;

use QrCommunication\VivaLocalTerminal\HttpClient;

/**
 * Session retrieval on the Local Terminal (P2P) API.
 *
 * After a sale/refund/preauth-completion returns `PROCESSING`, poll the session
 * to obtain the final outcome. The `payloadData` field is populated only once the
 * transaction has completed (with `state` SUCCESS or FAILURE).
 *
 * The ISV variant uses a trailing slash (`/pos/v1/sessions/{id}/`), handled
 * automatically when `Config::$useIsvEndpoints` is enabled.
 *
 * @see /pos/v1/sessions/{sessionId}
 */
final class Sessions
{
    public function __construct(
        private readonly HttpClient $http,
    ) {}

    /**
     * Retrieve a session's full details by its id — GET /pos/v1/sessions/{sessionId}.
     *
     * @param  string  $sessionId  The session UUID.
     * @return array{state?: string, sessionType?: string, sessionId?: string, payloadData?: array<string, mixed>}
     */
    public function get(string $sessionId): array
    {
        return $this->http->get('/pos/v1/sessions/'.rawurlencode($sessionId));
    }
}
