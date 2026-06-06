<?php

declare(strict_types=1);

namespace QrCommunication\VivaLocalTerminal\Enums;

/**
 * The `state` value returned by the Local Terminal API.
 *
 * Returned immediately by sale/refund/abort/etc. (PROCESSING, BUSY_ERROR, ...)
 * and as the terminal/session outcome by GET /pos/v1/sessions/{sessionId}
 * (SUCCESS, FAILURE).
 *
 * @see /pos/v1/sale, /pos/v1/sessions/{sessionId}
 */
enum SessionState: string
{
    /** The operation is in progress on the terminal. */
    case PROCESSING = 'PROCESSING';

    /** Another transaction is already in progress on the terminal. */
    case BUSY_ERROR = 'BUSY_ERROR';

    /** The terminal reported a server-side error. */
    case SERVER_ERROR = 'SERVER_ERROR';

    /** The given session id could not be found (abort). */
    case SESSION_NOT_FOUND_ERROR = 'SESSION_NOT_FOUND_ERROR';

    /** The transaction completed successfully (sessions outcome). */
    case SUCCESS = 'SUCCESS';

    /** The transaction completed with an error (sessions outcome). */
    case FAILURE = 'FAILURE';
}
