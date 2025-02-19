<?php

declare(strict_types=1);

namespace Apiera\Amqp;

use Apiera\Amqp\Enum\MessageEventEnum;
use Apiera\Amqp\Enum\MessageTypeEnum;
use Apiera\Amqp\Exception\InvalidMessageException;

final readonly class Message
{
    public function __construct(
        private string $id,
        private MessageTypeEnum $type,
        private MessageEventEnum $event,
    ) {
    }

    /**
     * @throws InvalidMessageException
     */
    public static function fromJson(string $json): self
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new InvalidMessageException('Invalid JSON format');
        }

        if (!isset($data['id'], $data['type'], $data['event'])) {
            throw new InvalidMessageException('Missing required fields');
        }

        try {
            return new self(
                id: $data['id'],
                type: MessageTypeEnum::from($data['type']),
                event: MessageEventEnum::from($data['event'])
            );
        } catch (\ValueError) {
            throw new InvalidMessageException('Invalid type or event value');
        }
    }

    /**
     * @throws \JsonException
     */
    public function toJson(): string
    {
        return json_encode([
            'id' => $this->id,
            'type' => $this->type->value,
            'event' => $this->event->value,
        ], JSON_THROW_ON_ERROR);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): MessageTypeEnum
    {
        return $this->type;
    }

    public function getEvent(): MessageEventEnum
    {
        return $this->event;
    }
}
