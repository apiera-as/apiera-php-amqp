<?php

declare(strict_types=1);

namespace Apiera\Amqp\Enum;

enum MessageTypeEnum: string
{
    case Attribute = 'Attribute';

    case AttributeTerm = 'AttributeTerm';

    case Brand = 'Brand';

    case Category = 'Category';

    case Distributor = 'Distributor';

    case Inventory = 'Inventory';

    case Product = 'Product';

    case Tag = 'Tag';

    case Variant = 'Variant';
}
