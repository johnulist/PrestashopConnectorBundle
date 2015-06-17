<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Processor;

use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use Pim\Bundle\PrestashopConnectorBundle\Guesser\NormalizerGuesser;
use Pim\Bundle\PrestashopConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\PrestashopConnectorBundle\Manager\AssociationTypeManager;
use Pim\Bundle\PrestashopConnectorBundle\Manager\AttributeManager;
use Pim\Bundle\PrestashopConnectorBundle\Manager\CurrencyManager;
use Pim\Bundle\PrestashopConnectorBundle\Manager\LocaleManager;
use Pim\Bundle\PrestashopConnectorBundle\Merger\PrestashopMappingMerger;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\AbstractNormalizer;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopSoapClientParametersRegistry;
use Pim\Bundle\TransformBundle\Converter\MetricConverter;

/**
 * Prestashop product processor.
 *
 */
class ProductProcessor extends AbstractProductProcessor
{
    /** @var metricConverter */
    protected $metricConverter;

    /** @var AssociationTypeManager */
    protected $associationTypeManager;

    /** @var string */
    protected $pimGrouped;

    /**
     * @param WebserviceGuesser                   $webserviceGuesser
     * @param NormalizerGuesser                   $normalizerGuesser
     * @param LocaleManager                       $localeManager
     * @param PrestashopMappingMerger                $storeViewMappingMerger
     * @param CurrencyManager                     $currencyManager
     * @param ChannelManager                      $channelManager
     * @param PrestashopMappingMerger                $categoryMappingMerger
     * @param PrestashopMappingMerger                $attributeMappingMerger
     * @param MetricConverter                     $metricConverter
     * @param AssociationTypeManager              $associationTypeManager
     * @param PrestashopSoapClientParametersRegistry $clientParametersRegistry
     * @param AttributeManager                    $attributeManager
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        NormalizerGuesser $normalizerGuesser,
        LocaleManager $localeManager,
        PrestashopMappingMerger $storeViewMappingMerger,
        CurrencyManager $currencyManager,
        ChannelManager $channelManager,
        PrestashopMappingMerger $categoryMappingMerger,
        PrestashopMappingMerger $attributeMappingMerger,
        MetricConverter $metricConverter,
        AssociationTypeManager $associationTypeManager,
        PrestashopSoapClientParametersRegistry $clientParametersRegistry,
        AttributeManager $attributeManager
    ) {
        parent::__construct(
            $webserviceGuesser,
            $normalizerGuesser,
            $localeManager,
            $storeViewMappingMerger,
            $currencyManager,
            $channelManager,
            $categoryMappingMerger,
            $attributeMappingMerger,
            $clientParametersRegistry,
            $attributeManager
        );

        $this->metricConverter        = $metricConverter;
        $this->associationTypeManager = $associationTypeManager;
    }

    /**
     * @return string
     */
    public function getPimGrouped()
    {
        return $this->pimGrouped;
    }

    /**
     * @param string $pimGrouped
     *
     * @return ProductProcessor
     */
    public function setPimGrouped($pimGrouped)
    {
        $this->pimGrouped = $pimGrouped;

        return $this;
    }

    /**
     * Function called before all process.
     */
    protected function beforeExecute()
    {
        parent::beforeExecute();

        $this->globalContext['pimGrouped']       = $this->pimGrouped;
        $this->globalContext['defaultStoreView'] = $this->getDefaultStoreView();
    }

    /**
     * {@inheritdoc}
     */
    public function process($items)
    {
        $items = is_array($items) ? $items : [$items];

        $this->beforeExecute();

        $processedItems = [];

        $prestashopProducts = $this->webservice->getProductsStatus($items);

        $channel = $this->channelManager->getChannelByCode($this->channel);

        foreach ($items as $product) {
            $context = array_merge(
                $this->globalContext,
                ['attributeSetId' => $this->getAttributeSetId($product->getFamily()->getCode(), $product)]
            );

            if ($this->prestashopProductExists($product, $prestashopProducts)) {
                if ($this->attributeSetChanged($product, $prestashopProducts)) {
                    $this->addWarning(
                        'The product family has changed of this product. This modification cannot be applied to '.
                        'prestashop. In order to change the family of this product, please manualy delete this product '.
                        'in prestashop and re-run this connector.',
                        [],
                        [
                            'id'                                                 => $product->getId(),
                            $product->getIdentifier()->getAttribute()->getCode() => $product->getIdentifier()->getData(),
                            'family'                                             => $product->getFamily()->getCode(),
                        ]
                    );
                }

                $context['create'] = false;
            } else {
                $context['create'] = true;
            }

            $this->metricConverter->convert($product, $channel);

            try {
                $processedItems[] = $this->normalizeProduct($product, $context);
            } catch (\Exception $e) {
                $this->addWarning(
                    $e->getMessage(),
                    [],
                    [
                        'id'                                                 => $product->getId(),
                        $product->getIdentifier()->getAttribute()->getCode() => $product->getIdentifier()->getData(),
                        'label'                                              => $product->getLabel(),
                        'family'                                             => $product->getFamily()->getCode(),
                    ]
                );
            }
        }

        return $processedItems;
    }

    /**
     * Normalize the given product.
     *
     * @param ProductInterface $product
     * @param array            $context
     *
     * @throws InvalidItemException If a normalization error occurs
     *
     * @return array processed item
     */
    protected function normalizeProduct(ProductInterface $product, $context)
    {
        $processedItem = $this->productNormalizer->normalize(
            $product,
            AbstractNormalizer::PRESTASHOP_FORMAT,
            $context
        );

        return $processedItem;
    }

    /**
     * Test if a product already exists on prestashop platform.
     *
     * @param ProductInterface $product
     * @param array            $prestashopProducts
     *
     * @return bool
     */
    protected function prestashopProductExists(ProductInterface $product, $prestashopProducts)
    {
        foreach ($prestashopProducts as $prestashopProduct) {
            if ($prestashopProduct['sku'] == $product->getIdentifier()->getData()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Test if the product attribute set changed.
     *
     * @param ProductInterface $product
     * @param array            $prestashopProducts
     *
     * @return bool
     */
    protected function attributeSetChanged(ProductInterface $product, $prestashopProducts)
    {
        foreach ($prestashopProducts as $prestashopProduct) {
            if ($prestashopProduct['sku'] == $product->getIdentifier()->getData() &&
                $prestashopProduct['set'] != $this->getAttributeSetId($product->getFamily()->getCode(), $product)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return array_merge(
            parent::getConfigurationFields(),
            [
                'pimGrouped' => [
                    'type'    => 'choice',
                    'options' => [
                        'choices' => $this->associationTypeManager->getAssociationTypeChoices(),
                        'help'    => 'pim_prestashop_connector.export.pimGrouped.help',
                        'label'   => 'pim_prestashop_connector.export.pimGrouped.label',
                        'attr' => [
                            'class' => 'select2',
                        ],
                    ],
                ],
            ]
        );
    }
}
