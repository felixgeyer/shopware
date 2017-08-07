<?php declare(strict_types=1);

namespace Shopware\Product\Writer\Field\Product;

use Shopware\Framework\Validation\ConstraintBuilder;
use Shopware\Product\Writer\Api\DateField;

class AvailableToField extends DateField
{
    public function __construct(ConstraintBuilder $constraintBuilder)
    {
        parent::__construct('availableTo', 'available_to', 'product', $constraintBuilder);
    }
}