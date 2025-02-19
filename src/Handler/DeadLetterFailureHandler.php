<?php

declare(strict_types=1);

namespace Apiera\Amqp\Handler;

use Apiera\Amqp\Channel;
use Apiera\Amqp\Enum\ExchangeTypeEnum;
use Apiera\Amqp\Exception\FailedException;
use Apiera\Amqp\Exchange;
use Apiera\Amqp\Interface\FailureHandlerInterface;
use Apiera\Amqp\MessageEnvelope;
use Apiera\Amqp\Queue;

final readonly class DeadLetterFailureHandler implements FailureHandlerInterface
{
    public function __construct(
        private ?Exchange $failureExchange = null,
        private ?Queue $failureQueue = null,
    ) {
    }

    /**
     * @throws \AMQPQueueException
     * @throws \AMQPExchangeException
     * @throws \JsonException
     * @throws \AMQPConnectionException
     * @throws \AMQPChannelException
     */
    public function failure(MessageEnvelope $envelope, FailedException $exception, Channel $channel): void
    {
        $failedQueueName = 'failed';

        $deadLetterExchange = $this->failureExchange ?? new Exchange(
            channel: $channel,
            type: ExchangeTypeEnum::DIRECT,
            name: 'failed',
            flags: []
        );

        $deadLetterQueue = $this->failureQueue ?? new Queue(
            channel: $channel,
            name: $failedQueueName,
            flags: [],
            arguments: [
                'x-queue-mode' => 'lazy',
            ]
        );

        $deadLetterExchange->declare();
        $deadLetterQueue->declare();
        $deadLetterQueue->bind($deadLetterExchange, $failedQueueName);

        $failureEnvelope = new MessageEnvelope(
            $envelope->getMessage(),
            [
                'x-error-message' => $exception->getMessage(),
                'x-error-class' => $exception::class,
                'x-error-time' => (new \DateTime())->format('c'),
                'x-error-context' => json_encode($exception->getContext()),
                'x-original-exchange' => $envelope->getOriginalExchange(),
                'x-original-routing-key' => $envelope->getOriginalExchange(),
            ]
        );

        $deadLetterExchange->publish($failureEnvelope, $failedQueueName);
    }
}
