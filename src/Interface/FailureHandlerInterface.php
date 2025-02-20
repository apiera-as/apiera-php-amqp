<?php

declare(strict_types=1);

namespace Apiera\Amqp\Interface;

use Apiera\Amqp\Channel;
use Apiera\Amqp\Exception\FailedException;
use Apiera\Amqp\MessageEnvelope;

interface FailureHandlerInterface
{
    public function failure(MessageEnvelope $envelope, FailedException $exception, Channel $channel): void;
}
