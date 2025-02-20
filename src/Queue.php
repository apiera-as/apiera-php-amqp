<?php

declare(strict_types=1);

namespace Apiera\Amqp;

use Apiera\Amqp\Util\Flags;

final class Queue
{
    private ?\AMQPQueue $queue = null;

    /**
     * @param \Apiera\Amqp\Enum\QueueFlagEnum[] $flags
     * @param array<string, mixed> $arguments
     */
    public function __construct(
        private readonly Channel $channel,
        private readonly string $name,
        private readonly array $flags = [],
        private readonly array $arguments = [],
    ) {
    }

    /**
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPQueueException
     */
    public function declare(): void
    {
        $queue = new \AMQPQueue($this->channel->getChannel());
        $queue->setName($this->name);
        $queue->setFlags(Flags::toAmqpFlags($this->flags));
        $queue->setArguments($this->arguments);
        $queue->declareQueue();

        $this->queue = $queue;
    }

    /**
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPQueueException
     */
    public function bind(Exchange $exchange, string $routingKey = ''): void
    {
        if ($this->queue === null) {
            $this->declare();
        }

        if ($this->queue === null) {
            throw new \RuntimeException('Queue is not initialized');
        }

        $this->queue->bind($exchange->getName(), $routingKey);
    }

    public function isDeclared(): bool
    {
        return $this->queue !== null;
    }

    public function getQueue(): \AMQPQueue
    {
        if ($this->queue === null) {
            throw new \RuntimeException('Queue is not declared');
        }

        return $this->queue;
    }
}
