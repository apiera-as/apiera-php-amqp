<?php

declare(strict_types=1);

namespace Apiera\Amqp;

final readonly class Publisher
{
    /**
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPExchangeException
     * @throws \JsonException
     */
    public function publish(
        Message $message,
        Exchange $exchange,
        string $routingKey = ''
    ): void {
        $messageEnvelope = new MessageEnvelope($message);
        $exchange->publish($messageEnvelope, $routingKey);
    }
}
