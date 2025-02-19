<?php

declare(strict_types=1);

namespace Apiera\Amqp\Enum;

use Apiera\Amqp\Interface\FlagInterface;

enum ExchangeFlagEnum implements FlagInterface
{
    case DURABLE;
    case PASSIVE;
    case AUTO_DELETE;
    case INTERNAL;

    public function toAmqpConstant(): int
    {
        return match ($this) {
            self::DURABLE => AMQP_DURABLE,
            self::PASSIVE => AMQP_PASSIVE,
            self::AUTO_DELETE => AMQP_AUTODELETE,
            self::INTERNAL => AMQP_INTERNAL,
        };
    }
}
