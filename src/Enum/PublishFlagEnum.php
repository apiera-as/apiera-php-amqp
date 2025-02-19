<?php

declare(strict_types=1);

namespace Apiera\Amqp\Enum;

use Apiera\Amqp\Interface\FlagInterface;

enum PublishFlagEnum implements FlagInterface
{
    case MANDATORY;
    case IMMEDIATE;

    public function toAmqpConstant(): int
    {
        return match ($this) {
            self::MANDATORY => AMQP_MANDATORY,
            self::IMMEDIATE => AMQP_IMMEDIATE,
        };
    }
}
