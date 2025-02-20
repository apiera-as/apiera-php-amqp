<?php

declare(strict_types=1);

namespace Apiera\Amqp\Exception;

final class RetryException extends \Exception
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message,
        private readonly int $maxRetryCount = 0,
        private readonly ?\DateTimeInterface $retryAfter = null,
        private readonly array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getMaxRetryCount(): int
    {
        return $this->maxRetryCount;
    }

    public function getRetryAfter(): ?\DateTimeInterface
    {
        return $this->retryAfter;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
