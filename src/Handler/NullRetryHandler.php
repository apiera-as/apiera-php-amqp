<?php

declare(strict_types=1);

namespace Apiera\Amqp\Handler;

use Apiera\Amqp\Channel;
use Apiera\Amqp\Exception\RetryException;
use Apiera\Amqp\Interface\RetryHandlerInterface;
use Apiera\Amqp\MessageEnvelope;

final readonly class NullRetryHandler implements RetryHandlerInterface
{
    public function retry(MessageEnvelope $envelope, RetryException $exception, Channel $channel): void
    {
        // Do nothing - messages will be rejected without retry
    }
}
