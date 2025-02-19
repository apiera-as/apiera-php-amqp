<?php

declare(strict_types=1);

namespace Apiera\Amqp\Interface;

interface FlagInterface
{
    public function toAmqpConstant(): int;
}
