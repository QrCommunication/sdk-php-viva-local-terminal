# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] - 2026-06-06

### Added

Initial release of the Viva.com **Local Terminal API** PHP SDK
(`qrcommunication/viva-local-terminal-sdk`).

This SDK targets the **peer-to-peer (P2P)** `/pos/v1/` protocol: it talks
**directly to an EFT POS card terminal over the local network** (terminal IP +
port), with **no Viva cloud endpoint** and **no authentication** (per the
official spec, a closed network requires no Authorization header).

- **Entry point** `VivaLocalTerminalClient(terminalBaseUrl, verifyTls, useIsvEndpoints, timeout, connectTimeout)`
  with readonly resource properties `$transactions`, `$sessions`, `$device`.
- **`Config`** — terminal base URL (scheme + IP + port), configurable TLS
  verification (`verifyTls`: `true` | PEM path | `false` for self-signed
  terminal certs on a closed LAN), and a global `useIsvEndpoints` toggle that
  switches every call to the ISV trailing-slash path variants
  (`/pos/v1/sale/`, `/pos/v1/sessions/{id}/`, ...).
- **`HttpClient`** — no auth, direct-to-terminal transport. Decodes JSON on 2xx
  and preserves the plain-string body the API returns on 400.
- **`Resources\Transactions`**
  - `sale()` — POST `/pos/v1/sale` (accepts `isvDetails` for ISV)
  - `capturePreauth()` — POST `/pos/v1/preauth-completion`
  - `refund()` — POST `/pos/v1/refund`
  - `unreferencedRefund()` — POST `/pos/v1/unreferenced-refund`
  - `abort()` — POST `/pos/v1/abort`
  - `aadeFimControl()` — POST `/pos/v1/aade-fim-control`
  - `retrieve()` — GET `/pos/v1/transactions` (filters as query params)
- **`Resources\Sessions`**
  - `get()` — GET `/pos/v1/sessions/{sessionId}`
- **`Resources\Device`**
  - `screenControl()` — POST `/pos/v1/awake-lock`
  - `brightness()` — POST `/pos/v1/brightness` (validates `0 < value <= 1`)
- **Enums** `SessionState`, `SessionType`, `TransactionTypeId`, `PaymentMethod`.
- **Exceptions** `VivaException` (base) and `ApiException`.
- Amounts are always integers in the currency minor unit (cents).
- 23 unit tests via Guzzle `MockHandler` (`tests/Fakes/GuzzleMockFactory.php`).
- `openapi.yaml` describing the `/pos/v1/*` surface.
