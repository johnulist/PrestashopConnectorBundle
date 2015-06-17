<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Normalizer;

use Pim\Bundle\CatalogBundle\Entity\Group;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\Webservice;
use Pim\Bundle\PrestashopConnectorBundle\Manager\PriceMappingManager;
use Pim\Bundle\PrestashopConnectorBundle\Manager\ComputedPriceNotMatchedException;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\Exception\InvalidPriceMappingException;
use Pim\Bundle\PrestashopConnectorBundle\Mapper\MappingCollection;

/**
 * A normalizer to transform a group entity into an array.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ConfigurableNormalizer extends AbstractNormalizer
{
    /** @staticvar string */
    const BASE_PRICE      = 'price';

    /** @staticvar string */
    const PRICE_CHANGES   = 'price_changes';

    /** @staticvar string */
    const ASSOCIATED_SKUS = 'associated_skus';

    /** @var ProductNormalizer */
    protected $productNormalizer;

    /** @var boolean */
    protected $visibility;

    /**
     * @param ChannelManager      $channelManager
     * @param ProductNormalizer   $productNormalizer
     * @param PriceMappingManager $priceMappingManager
     * @param boolean             $visibility
     */
    public function __construct(
        ChannelManager $channelManager,
        ProductNormalizer $productNormalizer,
        PriceMappingManager $priceMappingManager,
        $visibility
    ) {
        parent::__construct($channelManager);

        $this->productNormalizer   = $productNormalizer;
        $this->priceMappingManager = $priceMappingManager;
        $this->visibility          = $visibility;
    }

    /**
     *{@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $group    = $object['group'];
        $products = $object['products'];

        $sku = sprintf(Webservice::CONFIGURABLE_IDENTIFIER_PATTERN, $group->getCode());

        $processedItem[$context['defaultStoreView']] = $this->getDefaultConfigurable(
            $group,
            $sku,
            $context['attributeSetId'],
            $products,
            $context['prestashopAttributes'],
            $context['prestashopAttributesOptions'],
            $context['defaultLocale'],
            $context['website'],
            $context['channel'],
            $context['categoryMapping'],
            $context['attributeCodeMapping'],
            $context['create'],
            $context['pimGrouped'],
            $context['urlKey'],
            $context['skuFirst']
        );

        $images = $this->productNormalizer->getNormalizedImages(
            $products[0],
            $sku,
            $context['smallImageAttribute'],
            $context['baseImageAttribute'],
            $context['thumbnailAttribute']
        );

        if (count($images) > 0) {
            $processedItem[Webservice::IMAGES] = $images;
        }

        //For each storeview, we update the group only with localized attributes
        foreach ($this->getPimLocales($context['channel']) as $locale) {
            $storeView = $this->getStoreViewForLocale(
                $locale->getCode(),
                $context['prestashopStoreViews'],
                $context['storeViewMapping']
            );

            //If a locale for this storeview exist in PIM, we create a translated group in this locale
            if ($storeView && $storeView['code'] !== $context['defaultStoreView']) {
                $values = $this->productNormalizer->getValues(
                    $products[0],
                    $context['prestashopAttributes'],
                    $context['prestashopAttributesOptions'],
                    $locale->getCode(),
                    $context['channel'],
                    $context['categoryMapping'],
                    $context['attributeCodeMapping'],
                    true,
                    $context['pimGrouped'],
                    $context['urlKey'],
                    $context['skuFirst']
                );

                $values[ProductNormalizer::URL_KEY] = sprintf(
                    '%s-conf-%s',
                    $values[ProductNormalizer::URL_KEY],
                    $group->getId()
                );

                $processedItem[$storeView['code']] = [
                    $sku,
                    $values,
                    $storeView['code'],
                ];
            } else {
                if ($locale->getCode() !== $context['defaultLocale']) {
                    $this->localeNotFound($locale);
                }
            }
        }

        return $processedItem;
    }

    /**
     * Get default configurable.
     *
     * @param Group             $group
     * @param string            $sku
     * @param int               $attributeSetId
     * @param array             $products
     * @param array             $prestashopAttributes
     * @param array             $prestashopAttributesOptions
     * @param string            $locale
     * @param string            $website
     * @param string            $channel
     * @param MappingCollection $categoryMapping
     * @param MappingCollection $attributeMapping
     * @param boolean           $create
     * @param string            $pimGrouped
     * @param boolean           $urlKey
     * @param boolean           $skuFirst
     *
     * @return array
     *
     * @throws InvalidPriceMappingException
     */
    protected function getDefaultConfigurable(
        Group $group,
        $sku,
        $attributeSetId,
        $products,
        $prestashopAttributes,
        $prestashopAttributesOptions,
        $locale,
        $website,
        $channel,
        MappingCollection $categoryMapping,
        MappingCollection $attributeMapping,
        $create,
        $pimGrouped,
        $urlKey,
        $skuFirst
    ) {
        $priceMapping = $this->priceMappingManager->getPriceMapping($group, $products, $attributeMapping);

        try {
            $this->priceMappingManager->validatePriceMapping(
                $products,
                $priceMapping[self::PRICE_CHANGES],
                $priceMapping[self::BASE_PRICE],
                $attributeMapping
            );
        } catch (ComputedPriceNotMatchedException $e) {
            throw new InvalidPriceMappingException(
                sprintf(
                    'Price mapping cannot be automatically computed. This might be because an associated product has '.
                    'an inconsistant price regarding the other products of the variant group. %s',
                    $e->getMessage()
                )
            );
        }

        $associatedSkus = $this->getProductsSkus($products);

        $defaultProduct = $products[0];

        $defaultProductValues = $this->productNormalizer->getValues(
            $defaultProduct,
            $prestashopAttributes,
            $prestashopAttributesOptions,
            $locale,
            $channel,
            $categoryMapping,
            $attributeMapping,
            false,
            $pimGrouped,
            $urlKey,
            $skuFirst
        );

        $defaultProductValues[ProductNormalizer::VISIBILITY] = $this->visibility;
        $defaultProductValues[ProductNormalizer::URL_KEY] = $defaultProductValues[ProductNormalizer::URL_KEY].'-conf-'.$group->getId();

        $configurableAttributes['configurable_attributes'] = [];
        $attributes = $group->getAttributes();

        foreach ($attributes as $attribute) {
            $prestashopAttributeCode = strtolower($attributeMapping->getTarget($attribute->getCode()));
            $prestashopAttributeId = $prestashopAttributes[$prestashopAttributeCode]['attribute_id'];
            $configurableAttributes['configurable_attributes'][] = $prestashopAttributeId;
        }

        $defaultConfigurableValues = array_merge(
            $defaultProductValues,
            $configurableAttributes,
            $priceMapping,
            [self::ASSOCIATED_SKUS => $associatedSkus]
        );

        $defaultConfigurableValues['websites'] = [$website];

        if ($create) {
            $defaultConfigurable = $this->getNewConfigurable($sku, $defaultConfigurableValues, $attributeSetId);
        } else {
            $defaultConfigurable = $this->getUpdatedConfigurable($sku, $defaultConfigurableValues);
        }

        return $defaultConfigurable;
    }

    /**
     * Get the configurable for a new call.
     *
     * @param string $sku
     * @param array  $configurableValues
     * @param int    $attributeSetId
     *
     * @return array
     */
    protected function getNewConfigurable($sku, array $configurableValues, $attributeSetId)
    {
        return [
            AbstractNormalizer::MAGENTO_CONFIGURABLE_PRODUCT_KEY,
            $attributeSetId,
            $sku,
            $configurableValues,
        ];
    }

    /**
     * Get the configurable for an update call.
     *
     * @param string $sku
     * @param array  $configurableValues
     *
     * @return array
     */
    protected function getUpdatedConfigurable($sku, array $configurableValues)
    {
        return [
            $sku,
            $configurableValues,
        ];
    }

    /**
     * Get all products skus.
     *
     * @param array $products
     *
     * @return array
     */
    protected function getProductsSkus($products)
    {
        array_walk(
            $products,
            function (&$value) {
                $value = (string) $value->getIdentifier();
            }
        );

        return $products;
    }
}
