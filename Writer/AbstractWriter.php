<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Writer;

use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Pim\Bundle\PrestashopConnectorBundle\Item\PrestashopItemStep;

/**
 * Prestashop product writer.
 *
 */
abstract class AbstractWriter extends PrestashopItemStep implements ItemWriterInterface
{
}
