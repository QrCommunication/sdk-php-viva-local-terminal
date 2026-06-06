<?php

declare(strict_types=1);

namespace QrCommunication\VivaLocalTerminal\Enums;

/**
 * The `paymentMethod` accepted by the sale request — specifies the payment
 * method displayed to the user first by default. The user may still choose an
 * alternative method on the terminal.
 *
 * @see /pos/v1/sale
 */
enum PaymentMethod: string
{
    case CARD_PRESENT = 'CardPresent';

    case IRIS = 'Iris';
}
