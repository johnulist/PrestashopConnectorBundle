<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Writer;

use Pim\Bundle\PrestashopConnectorBundle\Manager\DeltaProductExportManager;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\PrestashopConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopRestClientParametersRegistry;

/**
 * Write delta product in Prestashop.
 *
 */
class DeltaProductWriter extends ProductWriter
{
    /** @var DeltaProductExportManager */
    protected $productExportManager;

    /**
     * @param WebserviceGuesser                   $webserviceGuesser
     * @param ChannelManager                      $channelManager
     * @param PrestashopRestClientParametersRegistry $clientParametersRegistry
     * @param DeltaProductExportManager           $productExportManager
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        ChannelManager $channelManager,
        PrestashopRestClientParametersRegistry $clientParametersRegistry,
        DeltaProductExportManager $productExportManager
    ) {
        parent::__construct($webserviceGuesser, $channelManager, $clientParametersRegistry);

        $this->productExportManager = $productExportManager;
    }

    /**
     * Compute an individual product and all its parts (translations).
     *
     * @param array $product
     */
    protected function computeProduct($product)
    {
        $sku = $this->getProductSku($product);

        parent::computeProduct($product);

        $this->productExportManager->updateProductExport($this->getJobInstance(), $sku);
    }

    /**
     * @return \Akeneo\Bundle\BatchBundle\Entity\JobInstance
     */
    protected function getJobInstance()
    {
        return $this->stepExecution->getJobExecution()->getJobInstance();
    }
}
