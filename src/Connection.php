<?php

declare(strict_types=1);

namespace Apiera\Amqp;

final class Connection
{
    private ?\AMQPConnection $connection = null;

    public function __construct(
        private readonly Configuration $configuration,
    ) {
    }

    /**
     * @throws \AMQPConnectionException
     */
    public function connect(): void
    {
        if ($this->connection !== null && $this->connection->isConnected()) {
            return;
        }

        $this->connection = new \AMQPConnection($this->configuration->getConnectionArguments());
        $this->connection->connect();
    }

    /**
     * @throws \AMQPConnectionException
     */
    public function disconnect(): void
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            return;
        }

        $this->connection->disconnect();
        $this->connection = null;
    }

    public function isConnected(): bool
    {
        return $this->connection !== null && $this->connection->isConnected();
    }

    public function getConnection(): \AMQPConnection
    {
        if ($this->connection === null) {
            throw new \RuntimeException('Connection is not established');
        }

        return $this->connection;
    }
}
