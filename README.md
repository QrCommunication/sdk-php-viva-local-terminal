# Viva.com Local Terminal SDK (PHP)

PHP SDK for the Viva.com **Local Terminal API** (`/pos/v1/`) — direct
**peer-to-peer (P2P)** communication with EFT POS card terminals.

[![PHP](https://img.shields.io/badge/php-%5E8.2-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

---

## ⚠️ Read first: this is NOT the Viva cloud API

This SDK does **not** talk to `api.vivapayments.com`. The Local Terminal API is
a **peer-to-peer protocol**: your application talks **directly to the card
terminal** over your local network, addressing the terminal's **own IP address
and port**.

| | Viva Cloud APIs | **Local Terminal API (this SDK)** |
|---|---|---|
| Base URL | `https://api.vivapayments.com` | **`https://<terminal-ip>:<port>`** |
| Network | Internet | **Local network (LAN), P2P** |
| Auth | OAuth2 / Basic / Bearer | **None** — closed network, no Authorization header |
| TLS | Public CA | Terminal **self-signed cert** (usually) |

> From the official spec: *"In a closed network environment, authentication is
> not required for peer-to-peer communication. This means that you can target the
> terminal without authentication, eliminating the need to include an
> Authorization tag in the header."*

### Network prerequisites

1. The host running your code and the terminal must be on the **same LAN**.
2. You must know the terminal's **IP address and port** (discoverable via the
   Viva.com Terminal app / your network, or via zeroconf/mDNS).
3. The terminal serves **HTTPS**, typically with a **self-signed certificate**.
   You must therefore either pin its CA bundle or disable TLS verification
   (LAN-only trust model) — see [TLS](#tls--self-signed-certificates) below.

---

## Installation

```bash
composer require qrcommunication/viva-local-terminal-sdk
```

Requires PHP 8.2+.

---

## Quick start

```php
use QrCommunication\VivaLocalTerminal\VivaLocalTerminalClient;

$terminal = new VivaLocalTerminalClient(
    terminalBaseUrl: 'https://192.168.1.50:8080', // the terminal's IP + port
    verifyTls: false,                              // self-signed terminal cert on a closed LAN
);

// 1) Start a sale (you generate the sessionId; a UUID v4 is recommended)
$sessionId = '4bdebe62-c211-4ca0-a994-b2fbea2061c5';

$ack = $terminal->transactions->sale(
    sessionId: $sessionId,
    amount: 1170,        // 11.70 in the currency minor unit (cents) — ALWAYS an int
    currencyCode: 978,   // ISO 4217 numeric (978 = EUR)
    merchantReference: 'order-123',
);
// $ack = ['state' => 'PROCESSING', 'sessionType' => 'SALE', 'sessionId' => '...']

// 2) Poll for the outcome once the cardholder has interacted with the terminal
$session = $terminal->sessions->get($sessionId);
if (($session['state'] ?? null) === 'SUCCESS') {
    $sale = $session['payloadData']['saleResponse'];
    echo "Approved: {$sale['transactionId']}";
}
```

### Configuring the terminal address

The terminal address is supplied entirely via `terminalBaseUrl` (scheme + IP +
port). There is no "demo/production" environment — you point at whichever
terminal you want to drive:

```php
$terminal = new VivaLocalTerminalClient(
    terminalBaseUrl: 'https://10.0.0.42:8080',
    verifyTls: '/etc/viva/terminal-ca.pem', // pin the terminal's CA bundle
    timeout: 30,
    connectTimeout: 10,
);
```

---

## Resources & methods

The client exposes three readonly resources.

### `$terminal->transactions`

| Method | HTTP | Endpoint |
|--------|------|----------|
| `sale(...)` | POST | `/pos/v1/sale` |
| `capturePreauth(...)` | POST | `/pos/v1/preauth-completion` |
| `refund(...)` | POST | `/pos/v1/refund` |
| `unreferencedRefund(...)` | POST | `/pos/v1/unreferenced-refund` |
| `abort(string $sessionId)` | POST | `/pos/v1/abort` |
| `aadeFimControl(string $token)` | POST | `/pos/v1/aade-fim-control` |
| `retrieve(...)` | GET | `/pos/v1/transactions` |

### `$terminal->sessions`

| Method | HTTP | Endpoint |
|--------|------|----------|
| `get(string $sessionId)` | GET | `/pos/v1/sessions/{sessionId}` |

### `$terminal->device`

| Method | HTTP | Endpoint |
|--------|------|----------|
| `screenControl(bool $wakeUpLock)` | POST | `/pos/v1/awake-lock` |
| `brightness(float $brightness)` | POST | `/pos/v1/brightness` |

---

## Examples

### Pre-authorization + capture

```php
// Pre-auth: set preauth: true on the sale
$terminal->transactions->sale(
    sessionId: $preauthSessionId,
    amount: 5000,
    currencyCode: 978,
    preauth: true,
);

// ...later, capture the pre-authorized transaction
$terminal->transactions->capturePreauth(
    sessionId: $captureSessionId,
    amount: 5000,
    transactionId: '03cfb0b9-2e4b-4610-b4d7-29c28a50a749',
    currencyCode: 978,
);
```

### Referenced refund / cancel

```php
$terminal->transactions->refund(
    sessionId: $refundSessionId,
    amount: 1170,
    transactionId: '03cfb0b9-2e4b-4610-b4d7-29c28a50a749',
    currencyCode: 978,
);
```

### Unreferenced refund

```php
$terminal->transactions->unreferencedRefund(
    sessionId: $sessionId,
    amount: 1170,
    merchantReference: 'goodwill-refund',
);
```

### Abort an in-progress sale

```php
$terminal->transactions->abort($sessionId);
```

### Retrieve historical transactions

```php
$result = $terminal->transactions->retrieve(
    cardNumber: '449332XXXXXX2179',
    transactionTypes: [5, 7],   // 5 = sale, 7 = reversal (single int also accepted)
    startDate: '2025-08-14T10:00:00.000Z',
    endDate: '2025-09-10T12:00:00.000Z',
);
foreach ($result['transactions'] ?? [] as $txn) {
    echo $txn['transactionId'].PHP_EOL;
}
```

### Device control

```php
$terminal->device->screenControl(wakeUpLock: true); // keep screen awake
$terminal->device->brightness(0.5);                 // 50% brightness (0 < value <= 1)
```

### AADE FIM control (Greece)

```php
$terminal->transactions->aadeFimControl(
    'ECR0210U/RCFB77000041/CMAC_K:1ED9F7AE0B2509281BBC2DE38EF2A12B:CC5FFF',
);
```

---

## ISV integrators (trailing-slash variants)

The Viva spec distinguishes **ISV** variants of the endpoints by a **trailing
slash** (`/pos/v1/sale/`, `/pos/v1/refund/`, `/pos/v1/sessions/{id}/`, ...).
Enable them globally on the client; ISV sale/refund additionally accept the
`isvDetails` object:

```php
$terminal = new VivaLocalTerminalClient(
    terminalBaseUrl: 'https://192.168.1.50:8080',
    useIsvEndpoints: true,   // every call now targets the trailing-slash path
);

$terminal->transactions->sale(
    sessionId: $sessionId,
    amount: 1170,
    currencyCode: 978,
    isvDetails: [
        'clientId' => 'xxxxx.apps.vivapayments.com',
        'clientSecret' => 'rvkj2f4iFavmm0P7qclcvj3tD',
        'amount' => 122,                                // ISV fee (cents) — not added to the sale total
        'merchantId' => 'f7287549-e56a-4d33-b936-567827d326d0', // multi-merchant
        'sourceCode' => '1234',
    ],
);
```

---

## Amounts

All amounts are **integers in the currency minor unit (cents)**. `11.70 EUR`
is `1170`. `currencyCode` is the **ISO 4217 numeric** code (`978` = EUR,
`826` = GBP).

---

## TLS / self-signed certificates

The terminal usually presents a **self-signed certificate**. The `verifyTls`
option maps to Guzzle's `verify`:

| Value | Behaviour |
|-------|-----------|
| `true` *(default)* | Verify against the system CA bundle |
| `'/path/to/ca.pem'` | Verify against a pinned CA/PEM bundle (recommended) |
| `false` | Disable verification (acceptable only on a trusted, closed LAN) |

---

## Error handling

The Local Terminal API returns JSON on success and a **plain string** body on
`400`. Both are surfaced through `ApiException`:

```php
use QrCommunication\VivaLocalTerminal\Exceptions\ApiException;

try {
    $terminal->transactions->sale(sessionId: $id, amount: 0);
} catch (ApiException $e) {
    // e.g. "ZeroconfException error message Amount cannot be null"
    echo $e->getMessage();
    echo $e->httpStatus;        // 400
    echo $e->getErrorText();    // raw string body when no JSON
}
```

A connection failure (terminal unreachable on the LAN) is also thrown as an
`ApiException` with `httpStatus = 0`.

---

## Development

```bash
composer install
vendor/bin/phpunit
```

Tests use a Guzzle `MockHandler` (`tests/Fakes/GuzzleMockFactory.php`) — no real
terminal is required.

---

## License

[MIT](LICENSE) © QrCommunication
