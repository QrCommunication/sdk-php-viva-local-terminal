<?php

declare(strict_types=1);

namespace QrCommunication\VivaLocalTerminal\Enums;

/**
 * Transaction type identifiers returned in transaction / session payloads
 * (`transactionTypeId`) and accepted as the `transactionTypes` query filter on
 * GET /pos/v1/transactions.
 *
 * @see /pos/v1/transactions, /pos/v1/sessions/{sessionId}
 */
enum TransactionTypeId: int
{
    case SALE = 5;

    case REFUND = 4;

    case REVERSAL = 7;

    case IRIS_SALE = 60;

    case IRIS_REFUND = 61;

    case IRIS_VOID = 85;
}
