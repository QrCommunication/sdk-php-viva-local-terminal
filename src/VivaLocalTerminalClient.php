<?php

declare(strict_types=1);

namespace QrCommunication\VivaLocalTerminal;

use QrCommunication\VivaLocalTerminal\Resources\Device;
use QrCommunication\VivaLocalTerminal\Resources\Sessions;
use QrCommunication\VivaLocalTerminal\Resources\Transactions;

/**
 * Viva.com Local Terminal SDK — main entry point.
 *
 * The Local Terminal API is a PEER-TO-PEER (P2P) protocol: this client talks
 * DIRECTLY to an EFT POS terminal over the local network. There is no Viva cloud
 * endpoint and no authentication — you address the terminal's own IP and port.
 *
 * Usage:
 *
 *     $terminal = new VivaLocalTerminalClient(
 *         terminalBaseUrl: 'https://192.168.1.50:8080',
 *         // self-signed terminal cert on a closed LAN:
 *         verifyTls: false,
 *     );
 *
 *     // Start a sale
 *     $sessionId = '4bdebe62-c211-4ca0-a994-b2fbea2061c5'; // your generated UUID
 *     $resp = $terminal->transactions->sale(
 *         sessionId: $sessionId,
 *         amount: 1170,            // 11.70 EUR, in cents
 *         currencyCode: 978,       // EUR
 *         merchantReference: 'order-123',
 *     );
 *     // $resp = ['state' => 'PROCESSING', 'sessionType' => 'SALE', 'sessionId' => ...]
 *
 *     // Poll for the result
 *     $session = $terminal->sessions->get($sessionId);
 *     // $session['state'] === 'SUCCESS' once the cardholder has paid
 *
 *     // Control the device
 *     $terminal->device->screenControl(wakeUpLock: true);
 *     $terminal->device->brightness(0.5);
 *
 * For ISV integrators, enable the ISV (trailing-slash) endpoint variants:
 *
 *     $terminal = new VivaLocalTerminalClient(
 *         terminalBaseUrl: 'https://192.168.1.50:8080',
 *         useIsvEndpoints: true,
 *     );
 *     // sale/refund now accept the `isvDetails` parameter and hit `/pos/v1/sale/`
 */
final class VivaLocalTerminalClient
{
    public readonly Transactions $transactions;

    public readonly Sessions $sessions;

    public readonly Device $device;

    private readonly Config $config;

    private readonly HttpClient $http;

    /**
     * @param  string  $terminalBaseUrl  The terminal's base URL: scheme + IP + port,
     *                                    e.g. `https://192.168.1.50:8080`.
     * @param  bool|string  $verifyTls  TLS verification. `true` (default) verifies against the
     *                                  system CA bundle; a string is a path to a custom CA/PEM
     *                                  bundle for the terminal's self-signed cert; `false`
     *                                  disables verification (closed-LAN trust model only).
     * @param  bool  $useIsvEndpoints  Target the ISV (trailing-slash) endpoint variants.
     * @param  int  $timeout  Per-request timeout in seconds.
     * @param  int  $connectTimeout  TCP connect timeout in seconds.
     */
    public function __construct(
        string $terminalBaseUrl,
        bool|string $verifyTls = true,
        bool $useIsvEndpoints = false,
        int $timeout = 30,
        int $connectTimeout = 10,
    ) {
        $this->config = new Config(
            terminalBaseUrl: $terminalBaseUrl,
            verifyTls: $verifyTls,
            useIsvEndpoints: $useIsvEndpoints,
            timeout: $timeout,
            connectTimeout: $connectTimeout,
        );

        $this->http = new HttpClient($this->config);

        $this->transactions = new Transactions($this->http);
        $this->sessions = new Sessions($this->http);
        $this->device = new Device($this->http);
    }

    public function getConfig(): Config
    {
        return $this->config;
    }
}
