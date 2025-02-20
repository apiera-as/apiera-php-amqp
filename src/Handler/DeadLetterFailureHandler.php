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
        private ?Exchange $deadLetterExchange = null,
        private ?Queue $deadLetterQueue = null,
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
        $deadLetterExchange = $this->deadLetterExchange ?? new Exchange(
            channel: $channel,
            type: ExchangeTypeEnum::DIRECT,
            name: 'failed',
            flags: []
        );

        $deadLetterQueue = $this->deadLetterQueue ?? new Queue(
            channel: $channel,
            name: $deadLetterExchange->getName(),
            flags: [],
            arguments: [
                'x-queue-mode' => 'lazy',
            ]
        );

        if (!$deadLetterExchange->isDeclared()) {
            $deadLetterExchange->declare();
        }

        if (!$deadLetterQueue->isDeclared()) {
            $deadLetterQueue->declare();
        }

        $deadLetterQueue->bind($deadLetterExchange, $deadLetterExchange->getName());

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

        $deadLetterExchange->publish($failureEnvelope, $deadLetterExchange->getName());
    }
}
