<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Writer;

use Pim\Bundle\PrestashopConnectorBundle\Webservice\SoapCallException;
use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;

/**
 * Prestashop product association writer.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductAssociationWriter extends AbstractWriter
{
    /**
     * {@inheritdoc}
     */
    public function write(array $productAssociationCallsBatchs)
    {
        $this->beforeExecute();

        foreach ($productAssociationCallsBatchs as $productAssociationCalls) {
            $this->handleProductAssociationCalls($productAssociationCalls);
            $this->stepExecution->incrementSummaryInfo('product_linked');
        }
    }

    /**
     * Handle product association calls.
     *
     * @param array $productAssociationCalls
     *
     * @throws InvalidItemException
     */
    protected function handleProductAssociationCalls(array $productAssociationCalls)
    {
        foreach ($productAssociationCalls['remove'] as $productAssociationRemoveCall) {
            try {
                $this->webservice->removeProductAssociation($productAssociationRemoveCall);
            } catch (SoapCallException $e) {
                throw new InvalidItemException(
                    sprintf(
                        'An error occured during a product association remove call. This may be due to a linked '.
                        'product that doesn\'t exist on Prestashop side. Error message : %s',
                        $e->getMessage()
                    ),
                    $productAssociationRemoveCall
                );
            }
        }

        foreach ($productAssociationCalls['create'] as $productAssociationCreateCall) {
            try {
                $this->webservice->createProductAssociation($productAssociationCreateCall);
            } catch (SoapCallException $e) {
                throw new InvalidItemException(
                    sprintf(
                        'An error occured during a product association add call. This may be due to a linked '.
                        'product that doesn\'t exist on Prestashop side. Error message : %s',
                        $e->getMessage()
                    ),
                    $productAssociationCreateCall
                );
            }
        }
    }
}
