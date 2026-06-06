# Viva.com Local Terminal SDK — Architecture & Implementation Guide

PHP SDK for the Viva.com **Local Terminal API** (`/pos/v1/`), a **peer-to-peer
(P2P)** protocol for driving EFT POS card terminals over the local network.

## The one thing that makes this SDK different

**There is no cloud and no authentication.** Every other Viva SDK (ISV, etc.)
authenticates against `api.vivapayments.com`. This one does **not**:

- **Transport**: the client connects **directly to the terminal** at
  `https://<terminal-ip>:<port>` (the `servers.url` in the spec is literally
  `https://X.X.X.X:PORT`, i.e. the terminal's LAN address).
- **Auth**: **none**. The spec states: *"In a closed network environment,
  authentication is not required for peer-to-peer communication [...] eliminating
  the need to include an Authorization tag in the header."* There is no
  `securitySchemes` / `securityDefinitions` block in the spec.
- **TLS**: the terminal serves HTTPS with a (usually) **self-signed cert**, so
  `Config::$verifyTls` is configurable (`true` | PEM path | `false`).

Do **not** add OAuth, tokens, or `Authorization` headers. If a future change
requires it, re-read the spec first.

## Package facts

- **Package**: `qrcommunication/viva-local-terminal-sdk`
- **Namespace**: `QrCommunication\VivaLocalTerminal\`
- **Entry point**: `VivaLocalTerminalClient`
- **Resources** (3): `transactions`, `sessions`, `device`
- **PHP**: 8.2+, `declare(strict_types=1)` everywhere, resources are `final`
- **Amounts**: always `int`, in the currency minor unit (cents)

## Layout

```
src/
  VivaLocalTerminalClient.php   Entry point; wires Config + HttpClient + resources
  Config.php                    terminalBaseUrl, verifyTls, useIsvEndpoints, timeouts; url() builder
  HttpClient.php                No-auth Guzzle wrapper; JSON on 2xx, plain string on 400
  Enums/
    SessionState.php            PROCESSING/BUSY_ERROR/SERVER_ERROR/SESSION_NOT_FOUND_ERROR/SUCCESS/FAILURE
    SessionType.php             SALE/CAPTURE_PREAUTH/CANCEL/UNREFERENCED_REFUND/ABORT
    TransactionTypeId.php       5=sale, 4=refund, 7=reversal, 60/61/85=IRIS
    PaymentMethod.php           CardPresent/Iris
  Exceptions/
    VivaException.php           Base RuntimeException; httpStatus + responseBody
    ApiException.php            Thrown on 4xx and transport failures
  Resources/
    Transactions.php            sale, capturePreauth, refund, unreferencedRefund, abort, aadeFimControl, retrieve
    Sessions.php                get
    Device.php                  screenControl (awake-lock), brightness
tests/
  Fakes/GuzzleMockFactory.php   Real HttpClient + Guzzle MockHandler (no token response needed)
  Unit/...                      One test class per resource + Config + client
```

## Endpoint → method map

| Method | HTTP | Endpoint |
|--------|------|----------|
| `transactions->sale()` | POST | `/pos/v1/sale` |
| `transactions->capturePreauth()` | POST | `/pos/v1/preauth-completion` |
| `transactions->refund()` | POST | `/pos/v1/refund` |
| `transactions->unreferencedRefund()` | POST | `/pos/v1/unreferenced-refund` |
| `transactions->abort()` | POST | `/pos/v1/abort` |
| `transactions->aadeFimControl()` | POST | `/pos/v1/aade-fim-control` |
| `transactions->retrieve()` | GET | `/pos/v1/transactions` |
| `sessions->get()` | GET | `/pos/v1/sessions/{sessionId}` |
| `device->screenControl()` | POST | `/pos/v1/awake-lock` |
| `device->brightness()` | POST | `/pos/v1/brightness` |

## Merchant vs ISV variants

The spec exposes the same endpoints twice: **merchant** (no trailing slash) and
**ISV** (trailing slash, e.g. `/pos/v1/sale/`). Rather than duplicate methods,
the SDK uses **one set of methods** plus a global `Config::$useIsvEndpoints`
toggle. `Config::url()` appends the trailing slash when the flag is on. ISV
sale/refund additionally accept an `isvDetails` array parameter (omitted from the
body when null, so it's harmless for merchant calls).

## Conventions (NON-NEGOTIABLE)

- `declare(strict_types=1)` at the top of every file.
- Resource classes are `final`, constructor-injected with `HttpClient`.
- Optional request fields use **named nullable parameters**, assembled with
  `array_filter(..., fn ($v) => $v !== null)` so nulls never reach the wire.
- Booleans that are meaningful as `false` are kept by the `!== null` filter
  predicate (do NOT use a plain truthy `array_filter`, which would drop `false`).
- Every public method has a PHPDoc with `@param`/`@return` and an endpoint ref.
- Amounts are `int` cents; `currencyCode` is ISO 4217 numeric.
- Error bodies may be plain strings (400) — `HttpClient` preserves them.

## Async / polling model

Sale/refund/preauth-completion return **immediately** with
`state = PROCESSING` and a `sessionId`. The caller generates the `sessionId`
(UUID) and later polls `sessions->get($sessionId)` until `state` is `SUCCESS`
or `FAILURE` and `payloadData` is populated.

## Testing

- One test per public method, driven by `GuzzleMockFactory` (Guzzle
  `MockHandler`). **No token response to pre-queue** — there is no auth.
- Assert the request method, the exact URL (merchant vs ISV trailing slash),
  the JSON body (incl. null-stripping), and the decoded response.
- Run: `composer install && vendor/bin/phpunit` (must be green).

## Anti-patterns (do not do)

- Adding OAuth / Bearer / Basic auth or any `Authorization` header.
- Introducing a cloud "environment" enum — the address is `terminalBaseUrl`.
- Forcing TLS verification on by default without a way to pin/disable it.
- Sending `null` fields on the wire (always `array_filter` first).
- Assuming JSON error bodies (400 returns a plain string).
