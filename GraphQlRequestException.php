<?php

declare(strict_types=1);

namespace BufferApi\Exception;

final class GraphQlRequestException extends BufferApiException
{
    /** @var array<int, array<string, mixed>> */
    private array $errors;

    /**
     * @param array<int, array<string, mixed>> $errors
     * @param array<string, mixed> $context
     */
    public function __construct(string $message, array $errors = [], array $context = [])
    {
        parent::__construct($message, 0, null, $context);
        $this->errors = $errors;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
