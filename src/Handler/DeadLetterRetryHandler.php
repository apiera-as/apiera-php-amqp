<?php

declare(strict_types=1);

namespace Apiera\Amqp\Handler;

use Apiera\Amqp\Channel;
use Apiera\Amqp\Enum\ExchangeTypeEnum;
use Apiera\Amqp\Exception\RetryException;
use Apiera\Amqp\Exchange;
use Apiera\Amqp\Interface\RetryHandlerInterface;
use Apiera\Amqp\MessageEnvelope;
use Apiera\Amqp\Queue;

final readonly class DeadLetterRetryHandler implements RetryHandlerInterface
{
    public function __construct(
        private ?Exchange $retryExchange = null,
        private ?Queue $retryQueue = null,
    ) {
    }

    /**
     * @throws \AMQPQueueException
     * @throws \AMQPExchangeException
     * @throws \AMQPConnectionException
     * @throws \JsonException
     * @throws \AMQPChannelException
     */
    public function retry(MessageEnvelope $envelope, RetryException $exception, Channel $channel): void
    {
        $delay = $this->calculateDelay($exception->getRetryAfter());

        $retryExchange = $this->retryExchange ?? new Exchange(
            channel: $channel,
            type: ExchangeTypeEnum::DIRECT,
            name: 'retry',
            flags: []
        );

        $retryQueueName = sprintf('retry_%d', $delay);

        $retryQueue = $this->retryQueue ?? new Queue(
            channel: $channel,
            name: $retryQueueName,
            flags: [],
            arguments: [
                'x-dead-letter-exchange' => $envelope->getOriginalExchange(),
                'x-dead-letter-routing-key' => $envelope->getOriginalRoutingKey(),
                'x-message-ttl' => $delay,
                'x-queue-mode' => 'lazy',
            ]
        );

        $retryExchange->declare();
        $retryQueue->declare();
        $retryQueue->bind($retryExchange, $retryQueueName);

        $retryEnvelope = new MessageEnvelope(
            $envelope->getMessage(),
            [
                'x-retry-count' => $envelope->getRetryCount() + 1,
                'x-retry-limit' => $exception->getMaxRetryCount(),
                'x-error-message' => $exception->getMessage(),
                'x-error-class' => $exception::class,
                'x-error-time' => (new \DateTime())->format('c'),
                'x-error-context' => json_encode($exception->getContext()),
                'x-dead-letter-exchange' => $envelope->getOriginalExchange(),
                'x-dead-letter-routing-key' => $envelope->getOriginalRoutingKey(),
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
