<?php

declare(strict_types=1);

namespace Apiera\Amqp\Handler;

use Apiera\Amqp\Channel;
use Apiera\Amqp\Exception\FailedException;
use Apiera\Amqp\Interface\FailureHandlerInterface;
use Apiera\Amqp\MessageEnvelope;

final readonly class NullFailureHandler implements FailureHandlerInterface
{
    public function failure(MessageEnvelope $envelope, FailedException $exception, Channel $channel): void
    {
        // Do nothing - failed messages will be rejected without moving to failure queue
    }
}
