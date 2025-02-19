<?php

declare(strict_types=1);

namespace Apiera\Amqp\Enum;

enum MessageEventEnum: string
{
    case OnCreate = 'onCreate';
    case OnUpdate = 'onUpdate';
    case OnDelete = 'onDelete';
}
