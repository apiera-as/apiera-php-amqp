<?php

declare(strict_types=1);

namespace Apiera\Amqp;

use Apiera\Amqp\Enum\ExchangeTypeEnum;
use Apiera\Amqp\Util\Flags;

final class Exchange
{
    private ?\AMQPExchange $exchange = null;

    /**
     * @param \Apiera\Amqp\Enum\ExchangeFlagEnum[] $flags
     */
    public function __construct(
        private readonly Channel $channel,
        private readonly ExchangeTypeEnum $type,
        private readonly string $name,
        private readonly array $flags = [],
    ) {
    }

    /**
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPExchangeException
     */
    public function declare(): void
    {
        $exchange = new \AMQPExchange($this->channel->getChannel());
        $exchange->setName($this->name);
        $exchange->setType($this->type->toAmqpConstant());
        $exchange->setFlags(Flags::toAmqpFlags($this->flags));

        $exchange->declareExchange();

        $this->exchange = $exchange;
    }

    /**
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPExchangeException
     * @throws \JsonException
     */
    public function publish(MessageEnvelope $messageEnvelope, string $routingKey = ''): void
    {
        if ($this->exchange === null) {
            $this->declare();
        }

        if ($this->exchange === null) {
            throw new \RuntimeException('Exchange is not initialized');
        }

        $attributes = [
            'delivery_mode' => 2,
            'headers' => $messageEnvelope->getHeaders(),
        ];

        $this->exchange->publish(
            $messageEnvelope->getMessage()->jsonNormalize(),
            $routingKey,
            AMQP_NOPARAM,
            $attributes
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isDeclared(): bool
    {
        return $this->exchange !== null;
    }

    public function getExchange(): \AMQPExchange
    {
        if ($this->exchange === null) {
            throw new \RuntimeException('Exchange is not declared');
        }

        return $this->exchange;
    }
}
