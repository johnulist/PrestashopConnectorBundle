<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Cleaner;

use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\CatalogBundle\Manager\ProductManager;
use Pim\Bundle\PrestashopConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopRestClientParametersRegistry;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\AbstractNormalizer;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\SoapCallException;

/**
 * Prestashop product cleaner
 * Abstract class used for ORM and MongoDB support.
 *
 */
abstract class AbstractProductCleaner extends Cleaner
{
    /** @var string Channel Code */
    protected $channel;

    /** @var array */
    protected $productTypesNotHandledByPim = [
        AbstractNormalizer::PRESTASHOP_BUNDLE_PRODUCT_KEY,
        AbstractNormalizer::PRESTASHOP_DOWNLOADABLE_PRODUCT_KEY,
        AbstractNormalizer::PRESTASHOP_VIRTUAL_PRODUCT_KEY,
    ];

    /**  @var string */
    protected $notCompleteAnymoreAction;

    /** @var boolean */
    protected $removeProductsNotHandledByPim;

    /**
     * @param WebserviceGuesser                   $webserviceGuesser
     * @param PrestashopRestClientParametersRegistry $clientParametersRegistry
     * @param ChannelManager                      $channelManager
     * @param ProductManager                      $productManager
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        PrestashopRestClientParametersRegistry $clientParametersRegistry,
        ChannelManager $channelManager,
        ProductManager $productManager
    ) {
        parent::__construct($webserviceGuesser, $clientParametersRegistry);

        $this->channelManager = $channelManager;
        $this->productManager = $productManager;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        parent::beforeExecute();

        $prestashopProducts  = $this->webservice->getProductsStatus();
        $exportedProducts = $this->getExportedProductsSkus();
        $pimProducts      = $this->getPimProductsSkus();

        foreach ($prestashopProducts as $product) {
            try {
                if (
                    AbstractNormalizer::PRESTASHOP_SIMPLE_PRODUCT_KEY === $product['type'] ||
                    in_array($product['type'], $this->productTypesNotHandledByPim)
                ) {
                    if (!in_array($product['sku'], $pimProducts)) {
                        $this->handleProductNotInPimAnymore($product);
                    } elseif (!in_array($product['sku'], $exportedProducts)) {
                        $this->handleProductNotCompleteAnymore($product);
                    }
                }
            } catch (SoapCallException $e) {
                throw new InvalidItemException($e->getMessage(), [json_encode($product)]);
            }
        }
    }

    /**
     * @return string channel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param string $channel channel
     *
     * @return AbstractProductCleaner
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return string
     */
    public function getNotCompleteAnymoreAction()
    {
        return $this->notCompleteAnymoreAction;
    }

    /**
     * @param string $notCompleteAnymoreAction
     *
     * @return AbstractProductCleaner
     */
    public function setNotCompleteAnymoreAction($notCompleteAnymoreAction)
    {
        $this->notCompleteAnymoreAction = $notCompleteAnymoreAction;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isRemoveProductsNotHandledByPim()
    {
        return $this->removeProductsNotHandledByPim;
    }

    /**
     * @param boolean $removeProductsNotHandledByPim
     *
     * @return AbstractProductCleaner
     */
    public function setRemoveProductsNotHandledByPim($removeProductsNotHandledByPim)
    {
        $this->removeProductsNotHandledByPim = $removeProductsNotHandledByPim;

        return $this;
    }

    /**
     * Get all products' skus in channel.
     *
     * @return array
     */
    abstract protected function getExportedProductsSkus();

    /**
     * Get all products' skus.
     *
     * @return array
     */
    abstract protected function getPimProductsSkus();

    /**
     * Handle products that are not in pim anymore.
     *
     * @param array $product
     */
    protected function handleProductNotInPimAnymore(array $product)
    {
        $this->handleProduct(
            $product,
            $this->notInPimAnymoreAction,
            $this->removeProductsNotHandledByPim
        );
    }

    /**
     * Handle products that are not in channel anymore.
     *
     * @param array $product
     */
    protected function handleProductNotCompleteAnymore(array $product)
    {
        $this->handleProduct(
            $product,
            $this->notCompleteAnymoreAction,
            $this->removeProductsNotHandledByPim
        );
    }

    /**
     * Handle product for the given action.
     *
     * @param array   $product
     * @param string  $notAnymoreAction
     * @param boolean $removeProductsNotHandledByPim
     */
    protected function handleProduct(array $product, $notAnymoreAction, $removeProductsNotHandledByPim = false)
    {
        if (
            false === $removeProductsNotHandledByPim &&
            in_array($product['type'], $this->productTypesNotHandledByPim)
        ) {
            $this->stepExecution->incrementSummaryInfo('product_not_removed');
            $this->addWarning('Non removed product\'s SKU: %sku%', ['%sku%' => $product['sku']], $product);

            return null;
        }

        if (self::DISABLE === $notAnymoreAction) {
            $this->webservice->disableProduct($product['sku']);
            $this->stepExecution->incrementSummaryInfo('product_disabled');
        } elseif (self::DELETE === $notAnymoreAction) {
            $this->webservice->deleteProduct($product['sku']);
            $this->stepExecution->incrementSummaryInfo('product_deleted');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return array_merge(
            parent::getConfigurationFields(),
            [
                'notCompleteAnymoreAction' => [
                    'type'    => 'choice',
                    'options' => [
                        'choices'  => [
                            Cleaner::DO_NOTHING => 'pim_prestashop_connector.export.do_nothing.label',
                            Cleaner::DISABLE    => 'pim_prestashop_connector.export.disable.label',
                            Cleaner::DELETE     => 'pim_prestashop_connector.export.delete.label',
                        ],
                        'required' => true,
                        'help'     => 'pim_prestashop_connector.export.notCompleteAnymoreAction.help',
                        'label'    => 'pim_prestashop_connector.export.notCompleteAnymoreAction.label',
                        'attr'     => ['class' => 'select2'],
                    ],
                ],
                'channel' => [
                    'type'    => 'choice',
                    'options' => [
                        'choices'  => $this->channelManager->getChannelChoices(),
                        'required' => true,
                    ],
                ],
                'removeProductsNotHandledByPim' => [
                    'type' => 'checkbox',
                    'options' => [
                        'help' => 'pim_prestashop_connector.export.removeProductsNotHandledByPim.help',
                        'label' => 'pim_prestashop_connector.export.removeProductsNotHandledByPim.label',
                    ],
                ],
            ]
        );
    }

    /**
     * @return \Pim\Bundle\CatalogBundle\Entity\Channel
     */
    protected function getChannelByCode()
    {
        return $this->channelManager->getChannelByCode($this->channel);
    }
}
