<?php

declare(strict_types=1);

namespace QrCommunication\VivaLocalTerminal\Enums;

/**
 * The `sessionType` returned by the Local Terminal API, indicating which kind
 * of operation a session represents.
 *
 * @see /pos/v1/sale, /pos/v1/refund, /pos/v1/abort, /pos/v1/sessions/{sessionId}
 */
enum SessionType: string
{
    /** A sale transaction. */
    case SALE = 'SALE';

    /** A capture (completion) of a pre-authorized sale. */
    case CAPTURE_PREAUTH = 'CAPTURE_PREAUTH';

    /** A refund / cancellation referencing an original transaction. */
    case CANCEL = 'CANCEL';

    /** An unreferenced refund (not linked to an original transaction). */
    case UNREFERENCED_REFUND = 'UNREFERENCED_REFUND';

    /** An abort of an in-progress SALE session. */
    case ABORT = 'ABORT';
}
