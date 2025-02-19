<?php

declare(strict_types=1);

namespace Apiera\Amqp\Interface;

use Apiera\Amqp\Channel;
use Apiera\Amqp\Exception\RetryException;
use Apiera\Amqp\MessageEnvelope;

interface RetryHandlerInterface
{
    public function retry(MessageEnvelope $envelope, RetryException $exception, Channel $channel): void;
}
