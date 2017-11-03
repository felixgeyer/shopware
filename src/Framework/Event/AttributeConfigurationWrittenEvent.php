<?php declare(strict_types=1);

namespace Shopware\Framework\Event;

use Shopware\Api\Write\WrittenEvent;

class AttributeConfigurationWrittenEvent extends WrittenEvent
{
    const NAME = 'attribute_configuration.written';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getEntityName(): string
    {
        return 'attribute_configuration';
    }
}
