<?php

declare(strict_types=1);

namespace Apiera\Amqp;

use Apiera\Amqp\Exception\InvalidMessageException;

final readonly class MessageEnvelope
{
    private const string X_RETRY_COUNT = 'x-retry-count';
    private const string X_ORIGINAL_EXCHANGE = 'x-original-exchange';
    private const string X_ORIGINAL_ROUTING_KEY = 'x-original-routing-key';

    /**
     * @param array<string, mixed> $headers
     */
    public function __construct(
        private Message $message,
        private array $headers = [],
    ) {
    }

    /**
     * @throws InvalidMessageException
     */
    public static function fromAMQPEnvelope(\AMQPEnvelope $envelope): self
    {
        try {
            $message = Message::fromJson($envelope->getBody());
            $headers = [];

            // Get headers from envelope
            foreach ($envelope->getHeaders() as $key => $value) {
                $headers[$key] = $value;
            }

            return new self($message, $headers);
        } catch (\Throwable $e) {
            throw new InvalidMessageException('Invalid AMQP message: ' . $e->getMessage());
        }
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    /**
     * @return array<string, mixed>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getRetryCount(): int
    {
        return (int)($this->headers[self::X_RETRY_COUNT] ?? 0);
    }

    public function getOriginalExchange(): ?string
    {
        return $this->headers[self::X_ORIGINAL_EXCHANGE] ?? null;
    }

    public function getOriginalRoutingKey(): ?string
    {
        return $this->headers[self::X_ORIGINAL_ROUTING_KEY] ?? null;
    }
}
