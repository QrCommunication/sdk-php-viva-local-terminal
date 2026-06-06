<?php

declare(strict_types=1);

namespace QrCommunication\VivaLocalTerminal\Resources;

use QrCommunication\VivaLocalTerminal\HttpClient;

/**
 * Device configuration operations on the Local Terminal (P2P) API.
 *
 * These control the physical terminal device directly: screen wake/sleep and
 * display brightness.
 *
 * @see /pos/v1/awake-lock, /pos/v1/brightness
 */
final class Device
{
    public function __construct(
        private readonly HttpClient $http,
    ) {}

    /**
     * Control the device screen / sleep mode — POST /pos/v1/awake-lock.
     *
     * @param  bool  $wakeUpLock  When true, the device wakes from sleep and the screen
     *                            stays active until the user unlocks it. When false, the
     *                            device reverts to its normal sleep behavior.
     * @return array{isSuccess?: bool}
     */
    public function screenControl(bool $wakeUpLock): array
    {
        return $this->http->post('/pos/v1/awake-lock', ['wakeUpLock' => $wakeUpLock]);
    }

    /**
     * Adjust the terminal app brightness — POST /pos/v1/brightness.
     *
     * @param  float  $brightness  Value in the range (0, 1], where 1.00 is maximum
     *                             brightness. A value of 0 is NOT allowed.
     * @return array{isSuccess?: bool}
     */
    public function brightness(float $brightness): array
    {
        if ($brightness <= 0.0 || $brightness > 1.0) {
            throw new \InvalidArgumentException(
                'brightness must be greater than 0 and at most 1.0 (0 is not allowed).',
            );
        }

        return $this->http->post('/pos/v1/brightness', ['brightness' => $brightness]);
    }
}
