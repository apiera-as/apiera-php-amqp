<?php

declare(strict_types=1);

namespace Apiera\Amqp;

use Apiera\Amqp\Exception\FailedException;
use Apiera\Amqp\Exception\RetryException;
use Apiera\Amqp\Handler\NullFailureHandler;
use Apiera\Amqp\Handler\NullRetryHandler;
use Apiera\Amqp\Interface\FailureHandlerInterface;
use Apiera\Amqp\Interface\RetryHandlerInterface;

final readonly class Consumer
{
    public function __construct(
        private Channel $channel,
        private RetryHandlerInterface $retryHandler = new NullRetryHandler(),
        private FailureHandlerInterface $failureHandler = new NullFailureHandler(),
    ) {
    }

    /**
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPEnvelopeException
     * @throws \AMQPQueueException
     *
     * @param callable(Message): void $callback
     */
    public function consume(Queue $queue, callable $callback): void
    {
        if (!$queue->isDeclared()) {
            $queue->declare();
        }

        $queue->getQueue()->consume(function (\AMQPEnvelope $amqpEnvelope) use ($callback, $queue): void {
            $envelope = MessageEnvelope::fromAMQPEnvelope($amqpEnvelope);

            $deliveryTag = $amqpEnvelope->getDeliveryTag();

            if ($deliveryTag === null) {
                throw new FailedException('Missing delivery tag in AMQP envelope');
            }

            try {
                $callback($envelope->getMessage());
                $queue->getQueue()->ack($deliveryTag);
            } catch (RetryException $exception) {
                if ($envelope->getRetryCount() >= $exception->getMaxRetryCount()) {
                    $failedException = new FailedException(
                        'Maximum retry attempts exceeded',
                        previous: $exception
                    );

                    $this->failureHandler->failure(
                        envelope: $envelope,
                        exception: $failedException,
                        channel: $this->channel
                    );
                    $queue->getQueue()->ack($deliveryTag);

                    return;
                }

                $this->retryHandler->retry(
                    envelope: $envelope,
                    exception: $exception,
                    channel: $this->channel
                );
                $queue->getQueue()->ack($deliveryTag);
            } catch (FailedException $exception) {
                $this->failureHandler->failure(
                    envelope: $envelope,
                    exception: $exception,
                    channel: $this->channel
                );
                $queue->getQueue()->ack($deliveryTag);
            } catch (\Throwable $exception) {
                $queue->getQueue()->nack($deliveryTag);

                throw new FailedException('Message processing failed', previous: $exception);
            }
        });
    }
}
