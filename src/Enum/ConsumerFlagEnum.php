<?php

declare(strict_types=1);

namespace Apiera\Amqp\Enum;

use Apiera\Amqp\Interface\FlagInterface;

enum ConsumerFlagEnum implements FlagInterface
{
    case AUTO_ACK;
    case JUST_CONSUME;

    public function toAmqpConstant(): int
    {
        return match ($this) {
            self::AUTO_ACK => AMQP_AUTOACK,
            self::JUST_CONSUME => AMQP_JUST_CONSUME,
        };
    }
}
