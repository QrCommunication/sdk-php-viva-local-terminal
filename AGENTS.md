# Viva.com Local Terminal SDK — Agent Instructions

See [CLAUDE.md](CLAUDE.md) for complete SDK architecture, patterns, and
implementation guidelines.

## Quick Reference

- **Package**: `qrcommunication/viva-local-terminal-sdk`
- **Namespace**: `QrCommunication\VivaLocalTerminal\`
- **Entry point**: `VivaLocalTerminalClient`
- **Resources** (3): `transactions`, `sessions`, `device`
- **Pattern**: Resource pattern (`$terminal->transactions->sale()`)
- **Transport**: **P2P / LAN** — talks DIRECTLY to the terminal at
  `https://<terminal-ip>:<port>`. NOT api.vivapayments.com.
- **Auth**: **NONE**. Closed-network P2P; no Authorization header (per spec).
  Do not add OAuth/tokens.
- **TLS**: terminal cert is usually self-signed → `verifyTls` (`true` | PEM path
  | `false`).
- **Merchant vs ISV**: same methods; `Config::$useIsvEndpoints` toggles the
  trailing-slash ISV path variants (`/pos/v1/sale/`). ISV sale/refund accept
  `isvDetails`.
- **Amounts**: always in cents (int). `currencyCode` is ISO 4217 numeric.
- **Async**: sale/refund return `PROCESSING` + `sessionId`; poll
  `sessions->get()` for the outcome.
- **PHP**: 8.2+ strict types, resources `final`.

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

## Sale + poll example

```php
$terminal = new VivaLocalTerminalClient(
    terminalBaseUrl: 'https://192.168.1.50:8080',
    verifyTls: false, // self-signed terminal cert on a closed LAN
);

$sessionId = '4bdebe62-c211-4ca0-a994-b2fbea2061c5';
$terminal->transactions->sale(sessionId: $sessionId, amount: 1170, currencyCode: 978);
$session = $terminal->sessions->get($sessionId); // poll until state SUCCESS/FAILURE
```

## Before committing

- `composer install && vendor/bin/phpunit` must be green.
- Never add an `Authorization` header / auth flow — re-read the spec first.
- Always `array_filter(..., fn ($v) => $v !== null)` request bodies (keep `false`).
