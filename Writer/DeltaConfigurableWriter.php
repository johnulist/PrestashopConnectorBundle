<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Writer;

use Pim\Bundle\PrestashopConnectorBundle\Manager\DeltaConfigurableExportManager;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\PrestashopConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopRestClientParametersRegistry;

/**
 * Write configurable in Prestashop.
 *
 */
class DeltaConfigurableWriter extends ProductWriter
{
    /** @var DeltaConfigurableExportManager */
    protected $configExportManager;

    /**
     * @param WebserviceGuesser                   $webserviceGuesser
     * @param ChannelManager                      $channelManager
     * @param PrestashopRestClientParametersRegistry $clientParametersRegistry
     * @param DeltaConfigurableExportManager      $configExportManager
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        ChannelManager $channelManager,
        PrestashopRestClientParametersRegistry $clientParametersRegistry,
        DeltaConfigurableExportManager $configExportManager
    ) {
        parent::__construct($webserviceGuesser, $channelManager, $clientParametersRegistry);

        $this->configExportManager = $configExportManager;
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

        $sku = substr($sku, 5); // due to "conf-" prefix for configurables
        $channel = $this->channelManager->getChannelByCode($this->getChannel());
        $this->configExportManager->setLastExportDate($channel, $this->getJobInstance(), $sku);
    }

    /**
     * @return \Akeneo\Bundle\BatchBundle\Entity\JobInstance
     */
    protected function getJobInstance()
    {
        return $this->stepExecution->getJobExecution()->getJobInstance();
    }
}
