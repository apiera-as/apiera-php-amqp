<?php

declare(strict_types=1);

namespace Integration;

use Apiera\Amqp\Channel;
use Apiera\Amqp\Configuration;
use Apiera\Amqp\Connection;
use Apiera\Amqp\Consumer;
use Apiera\Amqp\Enum\ExchangeTypeEnum;
use Apiera\Amqp\Exception\InvalidMessageException;
use Apiera\Amqp\Exception\RetryException;
use Apiera\Amqp\Exchange;
use Apiera\Amqp\Handler\DeadLetterFailureHandler;
use Apiera\Amqp\Handler\DeadLetterRetryHandler;
use Apiera\Amqp\Interface\MessageInterface;
use Apiera\Amqp\Publisher;
use Apiera\Amqp\Queue;
use PHPUnit\Framework\TestCase;

final class AmqpTest extends TestCase
{
    private Publisher $publisher;
    private Exchange $exchange;
    private Queue $queue;
    private Connection $connection;
    private Channel $channel;

    public function testMessagePublishAndConsume(): void
    {
        $consumer = new Consumer($this->channel);

        $testMessage = $this->getMessage();
        $messageReceived = false;
        $receivedMessage = null;

        // Publish test message
        $this->publisher->publish($testMessage, $this->exchange);

        try {
            // Use our Consumer class
            $consumer->consume(
                $this->queue,
                $testMessage,
                function (MessageInterface $message) use (&$messageReceived, &$receivedMessage): void {
                    $messageReceived = true;
                    $receivedMessage = $message;
                }
            );
        } catch (\AMQPQueueException $e) {
            // Expected when queue is empty or timeout occurs
            if (!str_contains($e->getMessage(), 'Consumer timeout')) {
                throw $e;
            }
        }

        $this->assertTrue($messageReceived, 'Message was not received');
        $this->assertNotNull($receivedMessage);
        $this->assertEquals($testMessage->id, $receivedMessage->id);
        $this->assertEquals($testMessage->type, $receivedMessage->type);
        $this->assertEquals($testMessage->event, $receivedMessage->event);
    }

    public function testMessagePublishAndConsumeWithRetry(): void
    {
        $consumer = new Consumer(
            $this->channel,
            new DeadLetterRetryHandler(),
            new DeadLetterFailureHandler()
        );

        $testMessage = $this->getMessage();
        $messageReceived = false;
        $receivedMessage = null;
        $retryCount = 0;

        $this->publisher->publish($testMessage, $this->exchange);

        try {
            $consumer->consume(
                $this->queue,
                $testMessage,
                function (MessageInterface $message) use (&$messageReceived, &$receivedMessage, &$retryCount): void {
                    $messageReceived = true;
                    $receivedMessage = $message;
                    $retryCount++;

                    throw new RetryException(
                        'Test retry',
                        maxRetryCount: 3,
                        retryAfter: new \DateTimeImmutable('+1 seconds')
                    );
                }
            );
        } catch (\AMQPQueueException $e) {
            // Expected when queue is empty or timeout occurs
            if (!str_contains($e->getMessage(), 'Consumer timeout')) {
                throw $e;
            }
        }

        $this->assertTrue($messageReceived, 'Message was not received');
        $this->assertNotNull($receivedMessage);
        $this->assertEquals($testMessage->id, $receivedMessage->id);
        $this->assertEquals($testMessage->type, $receivedMessage->type);
        $this->assertEquals($testMessage->event, $receivedMessage->event);
        // 1 original ack and 3 retries
        $this->assertEquals(4, $retryCount);
    }

    protected function setUp(): void
    {
        $configuration = new Configuration(
            host: 'localhost',
            port: 5673,
            login: 'test',
            password: 'test',
            vhost: 'test',
            // Set a short read timeout for testing
            readTimeout: 2
        );

        $this->connection = new Connection($configuration);
        $this->channel = new Channel($this->connection);
        $this->exchange = new Exchange($this->channel, ExchangeTypeEnum::DIRECT, 'test_exchange');
        $this->queue = new Queue($this->channel, 'test_queue');

        try {
            $this->connection->connect();
            $this->channel->open();
            $this->exchange->declare();
            $this->queue->declare();
            $this->queue->bind($this->exchange);
        } catch (\Throwable $e) {
            $this->markTestSkipped('RabbitMQ connection failed: ' . $e->getMessage());
        }

        $this->publisher = new Publisher();
    }

    protected function tearDown(): void
    {
        try {
            // Clean up the test queue
            if ($this->queue->isDeclared()) {
                $this->queue->getQueue()->delete();
            }

            if ($this->connection->isConnected()) {
                $this->connection->disconnect();
            }
        } catch (\Throwable $e) {
            // Log cleanup errors but don't fail the test
        }
    }

    private function getMessage(): MessageInterface
    {
        return new readonly class ('/api/v1/files/1234567890', 'attribute', 'onCreate') implements MessageInterface
        {
            public function __construct(
                public string $id,
                public string $type,
                public string $event,
            ) {
            }

            public function jsonNormalize(): string
            {
                return json_encode([
                    'id' => $this->id,
                    'type' => $this->type,
                    'event' => $this->event,
                ], JSON_THROW_ON_ERROR);
            }

            public function jsonDenormalize(string $json): MessageInterface
            {
                $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

                if (!isset($data['id'], $data['type'], $data['event'])) {
                    throw new InvalidMessageException('Missing required fields');
                }

                return new self(
                    id: $data['id'],
                    type: $data['type'],
                    event: $data['event']
                );
            }
        };
    }
}
