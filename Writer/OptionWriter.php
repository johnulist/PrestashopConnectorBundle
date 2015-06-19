<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Writer;

use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\RestCallException;

/**
 * Prestashop option writer.
 *
 */
class OptionWriter extends AbstractWriter
{
    /**
     * {@inheritdoc}
     */
    public function write(array $batches)
    {
        $this->beforeExecute();

        foreach ($batches as $options) {
            foreach ($options as $option) {
                try {
                    $this->webservice->createOption($option);
                    $this->stepExecution->incrementSummaryInfo('option_created');
                } catch (RestCallException $e) {
                    throw new InvalidItemException($e->getMessage(), [json_encode($option)]);
                }
            }
        }
    }
}
