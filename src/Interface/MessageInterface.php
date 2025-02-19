<?php

declare(strict_types=1);

namespace Apiera\Amqp\Interface;

interface MessageInterface
{
    /**
     * @throws \JsonException
     */
    public function jsonNormalize(): string;

    /**
     * @throws \Apiera\Amqp\Exception\InvalidMessageException
     */
    public function jsonDenormalize(string $json): self;
}
