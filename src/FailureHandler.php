<?php

declare(strict_types=1);

namespace Apiera\Amqp;

use Apiera\Amqp\Enum\ExchangeTypeEnum;
use Apiera\Amqp\Exception\FailedException;
use DateTime;

final readonly class FailureHandler
{
    /**
     * @throws \AMQPQueueException
     * @throws \AMQPExchangeException
     * @throws \JsonException
     * @throws \AMQPConnectionException
     * @throws \AMQPChannelException
     */
    public function failure(
        MessageEnvelope $envelope,
        FailedException $exception,
        Channel $channel,
        string $originalExchange,
        string $originalRoutingKey
    ): void {
        $failedQueueName = 'failed';

        // Setup dead letter exchange
        $deadLetterExchange = new Exchange(
            channel: $channel,
            type: ExchangeTypeEnum::DIRECT,
            name: 'failed',
            flags: []
        );

        $deadLetterExchange->declare();

        // Setup dead letter queue
        $deadLetterQueue = new Queue(
            channel: $channel,
            name: $failedQueueName,
            flags: [],
            arguments: [
                'x-queue-mode' => 'lazy',
            ]
        );
        $deadLetterQueue->declare();
        $deadLetterQueue->bind($deadLetterExchange, $failedQueueName);

        $failureEnvelope = new MessageEnvelope(
            $envelope->getMessage(),
            [
                'x-error-message' => $exception->getMessage(),
                'x-error-class' => $exception::class,
                'x-error-time' => (new DateTime())->format('c'),
                'x-error-context' => json_encode($exception->getContext()),
                'x-original-exchange' => $originalExchange,
                'x-original-routing-key' => $originalRoutingKey,
            ]
        );

        $deadLetterExchange->publish($failureEnvelope, $failedQueueName);
    }
}
