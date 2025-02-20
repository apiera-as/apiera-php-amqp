<?php

declare(strict_types=1);

namespace Apiera\Amqp\Util;

final class Flags
{
    /**
     * @param T[] $flags
     *
     * @template T of \Apiera\Amqp\Interface\FlagInterface
     */
    public static function toAmqpFlags(array $flags): int
    {
        return array_reduce($flags, fn (int $c, $f) => $c | $f->toAmqpConstant(), 0);
    }
}
