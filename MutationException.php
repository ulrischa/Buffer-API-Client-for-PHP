<?php

declare(strict_types=1);

namespace BufferApi\Exception;

final class MutationException extends BufferApiException
{
    private string $payloadType;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(string $message, string $payloadType, array $context = [])
    {
        parent::__construct($message, 0, null, $context);
        $this->payloadType = $payloadType;
    }

    public function getPayloadType(): string
    {
        return $this->payloadType;
    }
}
