<?php

declare(strict_types=1);

namespace Apiera\Amqp;

use Apiera\Amqp\Enum\ExchangeTypeEnum;
use Apiera\Amqp\Exception\RetryException;

final readonly class RetryHandler
{
    /**
     * @throws \AMQPQueueException
     * @throws \AMQPExchangeException
     * @throws \AMQPConnectionException
     * @throws \JsonException
     * @throws \AMQPChannelException
     */
    public function retry(
        MessageEnvelope $envelope,
        RetryException $exception,
        Channel $channel,
        string $originalExchange,
        string $originalRoutingKey
    ): void {
        $delay = $this->calculateDelay($exception->getRetryAfter());
        $retryQueueName = sprintf('retry_%d', $delay);

        // Setup retry exchange
        $retryExchange = new Exchange(
            channel: $channel,
            type: ExchangeTypeEnum::DIRECT,
            name: 'retry',
            flags: []
        );
        $retryExchange->declare();

        // Setup retry queue with dead letter exchange
        $retryQueue = new Queue(
            channel: $channel,
            name: $retryQueueName,
            flags: [],
            arguments: [
                'x-dead-letter-exchange' => $originalExchange,
                'x-dead-letter-routing-key' => $originalRoutingKey,
                'x-message-ttl' => $delay,
                'x-queue-mode' => 'lazy',
            ]
        );

        $retryQueue->declare();
        $retryQueue->bind($retryExchange, $retryQueueName);

        // Create updated envelope with retry metadata and error information
        $retryEnvelope = new MessageEnvelope(
            $envelope->getMessage(),
            [
                'x-retry-count' => $envelope->getRetryCount() + 1,
                'x-retry-limit' => $exception->getMaxRetryCount(),
                'x-error-message' => $exception->getMessage(),
                'x-error-class' => $exception::class,
                'x-error-time' => (new \DateTime())->format('c'),
                'x-error-context' => json_encode($exception->getContext()),
                'x-original-exchange' => $originalExchange,
                'x-original-routing-key' => $originalRoutingKey,
            ]
        );

        $retryExchange->publish($retryEnvelope, $retryQueueName);
    }

    private function calculateDelay(?\DateTimeInterface $retryAfter): int
    {
        if ($retryAfter === null) {
            // Default 5 seconds
            return 5000;
        }

        return max(0, $retryAfter->getTimestamp() - time()) * 1000;
    }
}
