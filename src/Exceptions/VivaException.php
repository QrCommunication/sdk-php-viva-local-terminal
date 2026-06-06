<?php

declare(strict_types=1);

namespace QrCommunication\VivaLocalTerminal\Exceptions;

class VivaException extends \RuntimeException
{
    /**
     * @param  array<string, mixed>|null  $responseBody
     */
    public function __construct(
        string $message,
        public readonly int $httpStatus = 0,
        public readonly ?array $responseBody = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $httpStatus, $previous);
    }

    /**
     * The Local Terminal API does not return structured error codes; it returns
     * a plain string body on 400. The raw body (if any) is preserved here.
     */
    public function getErrorText(): ?string
    {
        return $this->responseBody['message']
            ?? $this->responseBody['error']
            ?? $this->responseBody['raw']
            ?? null;
    }
}
