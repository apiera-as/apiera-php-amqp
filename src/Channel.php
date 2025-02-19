<?php

declare(strict_types=1);

namespace Apiera\Amqp;

final class Channel
{
    private ?\AMQPChannel $channel = null;

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * @throws \AMQPConnectionException
     */
    public function open(): void
    {
        if ($this->channel !== null) {
            return;
        }

        $this->channel = new \AMQPChannel($this->connection->getConnection());
    }

    public function close(): void
    {
        $this->channel = null;
    }

    public function getChannel(): \AMQPChannel
    {
        if ($this->channel === null) {
            throw new \RuntimeException('Channel is not initialized');
        }

        return $this->channel;
    }
}
