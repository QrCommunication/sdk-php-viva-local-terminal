<?php

declare(strict_types=1);

namespace QrCommunication\VivaLocalTerminal\Resources;

use QrCommunication\VivaLocalTerminal\HttpClient;

/**
 * Transaction operations on the Local Terminal (P2P) API.
 *
 * Every operation that starts a transaction returns IMMEDIATELY with a `state`
 * (typically `PROCESSING`) and a `sessionId`. The actual outcome is then polled
 * via {@see Sessions::get()} once the cardholder has interacted with the terminal.
 *
 * Amounts are ALWAYS integers in the currency's minor unit (cents).
 *
 * Merchant vs ISV variants: the same methods serve both. Whether the request hits
 * the ISV trailing-slash path (e.g. `/pos/v1/sale/`) is controlled globally by
 * `Config::$useIsvEndpoints`. ISV sale/refund additionally accept `isvDetails`
 * inside the body.
 *
 * @see /pos/v1/sale, /pos/v1/preauth-completion, /pos/v1/refund,
 *      /pos/v1/unreferenced-refund, /pos/v1/abort, /pos/v1/aade-fim-control,
 *      /pos/v1/transactions
 */
final class Transactions
{
    public function __construct(
        private readonly HttpClient $http,
    ) {}

    /**
     * Initiate a sale request — POST /pos/v1/sale.
     *
     * The terminal displays the amount and waits for the card. Use {@see Sessions::get()}
     * with the returned `sessionId` to retrieve the final result.
     *
     * @param  string  $sessionId  Caller-generated transaction session UUID.
     * @param  int  $amount  Amount to authorize, in cents.
     * @param  string|null  $merchantReference  Free-text merchant reference.
     * @param  string|null  $customerTrns  Free-text customer reference.
     * @param  bool|null  $preauth  Whether the payment is a pre-authorization.
     * @param  int|null  $tipAmount  Tip amount in cents (not compatible with $preauth).
     * @param  string|null  $paymentMethod  Default payment method shown first (e.g. "CardPresent").
     * @param  bool|null  $showTransactionResult  Whether to show the transaction result on screen.
     * @param  bool|null  $showReceipt  Whether to show the receipt and result on screen.
     * @param  int|null  $currencyCode  ISO 4217 numeric currency code (merchant currency).
     * @param  bool|null  $skipSurcharge  Disable surcharge even on eligible cards.
     * @param  string|null  $saleToAcquirerData  Base64-encoded JSON of extra acquirer metadata.
     * @param  int|null  $maxInstalments  Max instalments allowed (Greek merchants only).
     * @param  string|null  $aadeProviderId  AADE provider id (Greece).
     * @param  string|null  $aadeProviderSignatureData  AADE provider signature data (Greece).
     * @param  string|null  $aadeProviderSignature  AADE provider signature (Greece).
     * @param  bool|null  $aadePreloaded  AADE preloaded flag (Greece).
     * @param  int|null  $aadePreloadedDuration  AADE preloaded expiry duration (Greece).
     * @param  array<string, mixed>|null  $fiscalisationData  Viva Fiscalisation object.
     * @param  array<string, mixed>|null  $isvDetails  ISV partner / fee / multi-merchant object (ISV only).
     * @return array{state?: string, sessionType?: string, sessionId?: string}
     */
    public function sale(
        string $sessionId,
        int $amount,
        ?string $merchantReference = null,
        ?string $customerTrns = null,
        ?bool $preauth = null,
        ?int $tipAmount = null,
        ?string $paymentMethod = null,
        ?bool $showTransactionResult = null,
        ?bool $showReceipt = null,
        ?int $currencyCode = null,
        ?bool $skipSurcharge = null,
        ?string $saleToAcquirerData = null,
        ?int $maxInstalments = null,
        ?string $aadeProviderId = null,
        ?string $aadeProviderSignatureData = null,
        ?string $aadeProviderSignature = null,
        ?bool $aadePreloaded = null,
        ?int $aadePreloadedDuration = null,
        ?array $fiscalisationData = null,
        ?array $isvDetails = null,
    ): array {
        $body = array_filter([
            'sessionId' => $sessionId,
            'amount' => $amount,
            'merchantReference' => $merchantReference,
            'customerTrns' => $customerTrns,
            'preauth' => $preauth,
            'tipAmount' => $tipAmount,
            'paymentMethod' => $paymentMethod,
            'showTransactionResult' => $showTransactionResult,
            'showReceipt' => $showReceipt,
            'currencyCode' => $currencyCode,
            'skipSurcharge' => $skipSurcharge,
            'saleToAcquirerData' => $saleToAcquirerData,
            'maxInstalments' => $maxInstalments,
            'aadeProviderId' => $aadeProviderId,
            'aadeProviderSignatureData' => $aadeProviderSignatureData,
            'aadeProviderSignature' => $aadeProviderSignature,
            'aadePreloaded' => $aadePreloaded,
            'aadePreloadedDuration' => $aadePreloadedDuration,
            'fiscalisationData' => $fiscalisationData,
            'isvDetails' => $isvDetails,
        ], fn ($v) => $v !== null);

        return $this->http->post('/pos/v1/sale', $body);
    }

    /**
     * Capture (complete) a previously pre-authorized sale — POST /pos/v1/preauth-completion.
     *
     * @param  string  $sessionId  Caller-generated transaction session UUID.
     * @param  int  $amount  Amount to capture, in cents.
     * @param  string  $transactionId  Transaction id of the original pre-authorized sale.
     * @param  string|null  $merchantReference  Free-text merchant reference.
     * @param  string|null  $customerTrns  Free-text customer reference.
     * @param  int|null  $currencyCode  ISO 4217 numeric currency code.
     * @param  string|null  $saleToAcquirerData  Base64-encoded JSON of extra acquirer metadata.
     * @param  string|null  $aadeProviderId  AADE provider id (Greece).
     * @param  string|null  $aadeProviderSignatureData  AADE provider signature data (Greece).
     * @param  string|null  $aadeProviderSignature  AADE provider signature (Greece).
     * @param  bool|null  $aadePreloaded  AADE preloaded flag (Greece).
     * @param  int|null  $aadePreloadedDuration  AADE preloaded expiry duration (Greece).
     * @return array{state?: string, sessionType?: string, sessionId?: string}
     */
    public function capturePreauth(
        string $sessionId,
        int $amount,
        string $transactionId,
        ?string $merchantReference = null,
        ?string $customerTrns = null,
        ?int $currencyCode = null,
        ?string $saleToAcquirerData = null,
        ?string $aadeProviderId = null,
        ?string $aadeProviderSignatureData = null,
        ?string $aadeProviderSignature = null,
        ?bool $aadePreloaded = null,
        ?int $aadePreloadedDuration = null,
    ): array {
        $body = array_filter([
            'sessionId' => $sessionId,
            'amount' => $amount,
            'transactionId' => $transactionId,
            'merchantReference' => $merchantReference,
            'customerTrns' => $customerTrns,
            'currencyCode' => $currencyCode,
            'saleToAcquirerData' => $saleToAcquirerData,
            'aadeProviderId' => $aadeProviderId,
            'aadeProviderSignatureData' => $aadeProviderSignatureData,
            'aadeProviderSignature' => $aadeProviderSignature,
            'aadePreloaded' => $aadePreloaded,
            'aadePreloadedDuration' => $aadePreloadedDuration,
        ], fn ($v) => $v !== null);

        return $this->http->post('/pos/v1/preauth-completion', $body);
    }

    /**
     * Refund / cancel a transaction by its original transaction id — POST /pos/v1/refund.
     *
     * @param  string  $sessionId  Caller-generated transaction session UUID.
     * @param  int  $amount  Amount to refund, in cents.
     * @param  string  $transactionId  Transaction id of the original sale to refund.
     * @param  int|null  $orderCode  Order code of the original sale.
     * @param  int|null  $shortOrderCode  Short order code (filter helper).
     * @param  string|null  $merchantReference  Free-text merchant reference.
     * @param  string|null  $customerTrns  Free-text customer reference.
     * @param  int|null  $currencyCode  ISO 4217 numeric currency code.
     * @param  bool|null  $showTransactionResult  Whether to show the transaction result on screen.
     * @param  bool|null  $showReceipt  Whether to show the receipt and result on screen.
     * @param  string|null  $txnDateFrom  ISO 8601 lower bound for locating the transaction.
     * @param  string|null  $txnDateTo  ISO 8601 upper bound for locating the transaction.
     * @param  string|null  $aadeProviderId  AADE provider id (Greece).
     * @param  string|null  $aadeProviderSignatureData  AADE provider signature data (Greece).
     * @param  string|null  $aadeProviderSignature  AADE provider signature (Greece).
     * @return array{state?: string, sessionType?: string, sessionId?: string}
     */
    public function refund(
        string $sessionId,
        int $amount,
        string $transactionId,
        ?int $orderCode = null,
        ?int $shortOrderCode = null,
        ?string $merchantReference = null,
        ?string $customerTrns = null,
        ?int $currencyCode = null,
        ?bool $showTransactionResult = null,
        ?bool $showReceipt = null,
        ?string $txnDateFrom = null,
        ?string $txnDateTo = null,
        ?string $aadeProviderId = null,
        ?string $aadeProviderSignatureData = null,
        ?string $aadeProviderSignature = null,
    ): array {
        $body = array_filter([
            'sessionId' => $sessionId,
            'amount' => $amount,
            'transactionId' => $transactionId,
            'orderCode' => $orderCode,
            'shortOrderCode' => $shortOrderCode,
            'merchantReference' => $merchantReference,
            'customerTrns' => $customerTrns,
            'currencyCode' => $currencyCode,
            'showTransactionResult' => $showTransactionResult,
            'showReceipt' => $showReceipt,
            'txnDateFrom' => $txnDateFrom,
            'txnDateTo' => $txnDateTo,
            'aadeProviderId' => $aadeProviderId,
            'aadeProviderSignatureData' => $aadeProviderSignatureData,
            'aadeProviderSignature' => $aadeProviderSignature,
        ], fn ($v) => $v !== null);

        return $this->http->post('/pos/v1/refund', $body);
    }

    /**
     * Issue an unreferenced refund (not linked to an original transaction) —
     * POST /pos/v1/unreferenced-refund.
     *
     * @param  string  $sessionId  Caller-generated transaction session UUID.
     * @param  int  $amount  Amount to refund, in cents.
     * @param  string|null  $merchantReference  Free-text merchant reference.
     * @param  string|null  $customerTrns  Free-text customer reference.
     * @param  bool|null  $showTransactionResult  Whether to show the transaction result on screen.
     * @param  bool|null  $showReceipt  Whether to show the receipt and result on screen.
     * @param  string|null  $aadeProviderId  AADE provider id (Greece).
     * @param  string|null  $aadeProviderSignatureData  AADE provider signature data (Greece).
     * @param  string|null  $aadeProviderSignature  AADE provider signature (Greece).
     * @param  array<string, mixed>|null  $fiscalisationData  Viva Fiscalisation object.
     * @return array{state?: string, sessionType?: string, sessionId?: string}
     */
    public function unreferencedRefund(
        string $sessionId,
        int $amount,
        ?string $merchantReference = null,
        ?string $customerTrns = null,
        ?bool $showTransactionResult = null,
        ?bool $showReceipt = null,
        ?string $aadeProviderId = null,
        ?string $aadeProviderSignatureData = null,
        ?string $aadeProviderSignature = null,
        ?array $fiscalisationData = null,
    ): array {
        $body = array_filter([
            'sessionId' => $sessionId,
            'amount' => $amount,
            'merchantReference' => $merchantReference,
            'customerTrns' => $customerTrns,
            'showTransactionResult' => $showTransactionResult,
            'showReceipt' => $showReceipt,
            'aadeProviderId' => $aadeProviderId,
            'aadeProviderSignatureData' => $aadeProviderSignatureData,
            'aadeProviderSignature' => $aadeProviderSignature,
            'fiscalisationData' => $fiscalisationData,
        ], fn ($v) => $v !== null);

        return $this->http->post('/pos/v1/unreferenced-refund', $body);
    }

    /**
     * Abort an in-progress SALE session — POST /pos/v1/abort.
     *
     * @param  string  $sessionId  The session id of the SALE to abort.
     * @return array{state?: string, sessionType?: string, sessionId?: string}
     */
    public function abort(string $sessionId): array
    {
        return $this->http->post('/pos/v1/abort', ['sessionId' => $sessionId]);
    }

    /**
     * Create an AADE FIM control action — POST /pos/v1/aade-fim-control.
     *
     * Creates the Echo Message (to fetch the Master Key) and the Control Message
     * (to create the Session Key), validating the Master Key, KCV and encrypted
     * Session Key (Greek AADE fiscalisation).
     *
     * @param  string  $token  The FIMAS control token including the AADE token.
     * @return array{status?: string, message?: string}
     */
    public function aadeFimControl(string $token): array
    {
        return $this->http->post('/pos/v1/aade-fim-control', ['token' => $token]);
    }

    /**
     * Retrieve historical transactions with optional filters —
     * GET /pos/v1/transactions.
     *
     * All filters are optional. `transactionTypes` may be a single id or a list
     * (the API accepts the param repeated); pass an array to filter on several.
     *
     * @param  string|null  $cardNumber  Masked card number filter.
     * @param  string|null  $orderCode  Order code filter.
     * @param  int|array<int, int>|null  $transactionTypes  Transaction type id(s) (5=sale, 4=refund, ...).
     * @param  string|null  $transactionStatus  Transaction status filter.
     * @param  string|null  $startDate  ISO 8601 lower bound.
     * @param  string|null  $endDate  ISO 8601 upper bound.
     * @param  bool|null  $isAadeAutonomouslyOnly  Filter for AADE autonomous transactions only.
     * @return array{isSuccess?: bool, transactions?: array<int, array<string, mixed>>}
     */
    public function retrieve(
        ?string $cardNumber = null,
        ?string $orderCode = null,
        int|array|null $transactionTypes = null,
        ?string $transactionStatus = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?bool $isAadeAutonomouslyOnly = null,
    ): array {
        $query = array_filter([
            'cardNumber' => $cardNumber,
            'orderCode' => $orderCode,
            'transactionTypes' => $transactionTypes,
            'transactionStatus' => $transactionStatus,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'isAadeAutonomouslyOnly' => $isAadeAutonomouslyOnly === null
                ? null
                : ($isAadeAutonomouslyOnly ? 'true' : 'false'),
        ], fn ($v) => $v !== null);

        return $this->http->get('/pos/v1/transactions', $query);
    }
}
