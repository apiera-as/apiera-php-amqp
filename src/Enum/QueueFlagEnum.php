<?php

declare(strict_types=1);

namespace Apiera\Amqp\Enum;

use Apiera\Amqp\Interface\FlagInterface;

enum QueueFlagEnum implements FlagInterface
{
    case DURABLE;
    case PASSIVE;
    case EXCLUSIVE;
    case AUTO_DELETE;

    public function toAmqpConstant(): int
    {
        return match ($this) {
            self::DURABLE => AMQP_DURABLE,
            self::PASSIVE => AMQP_PASSIVE,
            self::EXCLUSIVE => AMQP_EXCLUSIVE,
            self::AUTO_DELETE => AMQP_AUTODELETE,
        };
    }
}
