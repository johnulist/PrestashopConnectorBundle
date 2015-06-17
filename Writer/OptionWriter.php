<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Writer;

use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\SoapCallException;

/**
 * Prestashop option writer.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
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
                } catch (SoapCallException $e) {
                    throw new InvalidItemException($e->getMessage(), [json_encode($option)]);
                }
            }
        }
    }
}
