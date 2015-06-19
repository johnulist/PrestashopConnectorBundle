<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Cleaner;

use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopRestClientParametersRegistry;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\PrestashopConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\CatalogBundle\Manager\ProductManager;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\AbstractNormalizer;
use Pim\Bundle\PrestashopConnectorBundle\Manager\GroupManager;

/**
 * Prestashop configurable cleaner for ORM implementation.
 *
 */
class ConfigurableCleaner extends AbstractProductCleaner
{
    /** @var GroupManager */
    protected $groupManager;

    /**
     * @param WebserviceGuesser                   $webserviceGuesser
     * @param PrestashopRestClientParametersRegistry $clientParametersRegistry
     * @param ChannelManager                      $channelManager
     * @param ProductManager                      $productManager
     * @param GroupManager                        $groupManager
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        PrestashopRestClientParametersRegistry $clientParametersRegistry,
        ChannelManager $channelManager,
        ProductManager $productManager,
        GroupManager $groupManager
    ) {
        parent::__construct($webserviceGuesser, $clientParametersRegistry, $channelManager, $productManager);

        $this->groupManager = $groupManager;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        parent::beforeExecute();

        $prestashopProducts  = $this->webservice->getProductsStatus();
        $pimConfigurables = $this->getPimConfigurablesSkus();

        foreach ($prestashopProducts as $product) {
            if ($product['type'] === AbstractNormalizer::PRESTASHOP_CONFIGURABLE_PRODUCT_KEY &&
                !in_array($product['sku'], $pimConfigurables)
            ) {
                $this->handleProductNotInPimAnymore($product);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getExportedProductsSkus()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getPimProductsSkus()
    {
        return [];
    }

    /**
     * Get all variant group skus.
     *
     * @return array
     */
    protected function getPimConfigurablesSkus()
    {
        return $this->groupManager->getRepository()->getVariantGroupSkus();
    }
}
