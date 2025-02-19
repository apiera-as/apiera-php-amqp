<?php

declare(strict_types=1);

namespace Apiera\Amqp\Enum;

enum ExchangeTypeEnum
{
    case DIRECT;
    case FANOUT;
    case TOPIC;
    case HEADERS;

    public function toAmqpConstant(): string
    {
        return match ($this) {
            self::DIRECT => AMQP_EX_TYPE_DIRECT,
            self::FANOUT => AMQP_EX_TYPE_FANOUT,
            self::TOPIC => AMQP_EX_TYPE_TOPIC,
            self::HEADERS => AMQP_EX_TYPE_HEADERS,
        };
    }

    public function supportsRoutingKey(): bool
    {
        return match ($this) {
            self::DIRECT, self::TOPIC => true,
            self::FANOUT, self::HEADERS => false,
        };
    }
}
