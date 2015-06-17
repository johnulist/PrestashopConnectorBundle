<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Normalizer;

use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\CatalogBundle\Model\AbstractProductMedia;
use Pim\Bundle\CatalogBundle\Manager\MediaManager;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use Pim\Bundle\PrestashopConnectorBundle\Filter\ExportableLocaleFilter;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\Webservice;
use Pim\Bundle\PrestashopConnectorBundle\Manager\CategoryMappingManager;
use Pim\Bundle\PrestashopConnectorBundle\Manager\AssociationTypeManager;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\Exception\CategoryNotFoundException;
use Pim\Bundle\PrestashopConnectorBundle\Mapper\MappingCollection;
use Gedmo\Sluggable\Util\Urlizer;

/**
 * A normalizer to transform a product entity into an array.
 *
 */
class ProductNormalizer extends AbstractNormalizer implements ProductNormalizerInterface
{
    /** @staticvar string */
    const VISIBILITY = 'visibility';

    /** @staticvar string */
    const URL_KEY    = 'url_key';

    /** @staticvar string */
    const NAME       = 'name';

    /** @staticvar string */
    const ENABLED    = 'status';

    /** @var boolean */
    protected $enabled;

    /** @var boolean */
    protected $visibility;

    /** @var string */
    protected $currencyCode;

    /** @var MediaManager */
    protected $mediaManager;

    /** @var AssociationTypeManager */
    protected $associationTypeManager;

    /** @var ProductValueNormalizer */
    protected $productValueNormalizer;

    /** @var ExportableLocaleFilter */
    protected $localeFilter;

    /**
     * @param ChannelManager         $channelManager
     * @param MediaManager           $mediaManager
     * @param ProductValueNormalizer $productValueNormalizer
     * @param CategoryMappingManager $categoryMappingManager
     * @param AssociationTypeManager $associationTypeManager
     * @param ExportableLocaleFilter $localeFilter
     * @param bool                   $enabled
     * @param bool                   $visibility
     * @param bool                   $variantMemberVisibility
     * @param string                 $currencyCode
     * @param string                 $prestashopUrl
     */
    public function __construct(
        ChannelManager $channelManager,
        MediaManager $mediaManager,
        ProductValueNormalizer $productValueNormalizer,
        CategoryMappingManager $categoryMappingManager,
        AssociationTypeManager $associationTypeManager,
        ExportableLocaleFilter $localeFilter,
        $enabled,
        $visibility,
        $variantMemberVisibility,
        $currencyCode,
        $prestashopUrl
    ) {
        parent::__construct($channelManager);

        $this->mediaManager            = $mediaManager;
        $this->productValueNormalizer  = $productValueNormalizer;
        $this->categoryMappingManager  = $categoryMappingManager;
        $this->associationTypeManager  = $associationTypeManager;
        $this->localeFilter            = $localeFilter;
        $this->enabled                 = $enabled;
        $this->visibility              = $visibility;
        $this->variantMemberVisibility = $variantMemberVisibility;
        $this->currencyCode            = $currencyCode;
        $this->prestashopUrl              = $prestashopUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($product, $format = null, array $context = [])
    {
        $processedItem = [];

        $processedItem[$context['defaultStoreView']] = $this->getDefaultProduct(
            $product,
            $context['prestashopAttributes'],
            $context['prestashopAttributesOptions'],
            $context['attributeSetId'],
            $context['defaultLocale'],
            $context['channel'],
            $context['website'],
            $context['categoryMapping'],
            $context['attributeCodeMapping'],
            $context['pimGrouped'],
            $context['create'],
            $context['defaultStoreView'],
            $context['urlKey'],
            $context['skuFirst']
        );

        $images = $this->getNormalizedImages(
            $product,
            $product->getIdentifier(),
            $context['smallImageAttribute'],
            $context['baseImageAttribute'],
            $context['thumbnailAttribute']
        );

        if (count($images) > 0) {
            $processedItem[Webservice::IMAGES] = $images;
        }

        $channel = $this->channelManager->getChannelByCode($context['channel']);
        $exportableLocales = $this->localeFilter->apply($product, $channel);
        //For each storeview, we update the product only with localized attributes
        foreach ($exportableLocales as $locale) {
            $storeView = $this->getStoreViewForLocale(
                $locale->getCode(),
                $context['prestashopStoreViews'],
                $context['storeViewMapping']
            );

            //If a locale for this storeview exist in PIM, we create a translated product in this locale
            if ($storeView && $storeView['code'] !== $context['defaultStoreView']) {
                $values = $this->getValues(
                    $product,
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

                $processedItem[$storeView['code']] = [
                    (string) $product->getIdentifier(),
                    $values,
                    $storeView['code'],
                    'sku',
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
     * Get all images of a product normalized.
     *
     * @param ProductInterface $product
     * @param string           $sku
     * @param string           $smallImageAttribute
     * @param string           $baseImageAttribute
     * @param string           $thumbnailAttribute
     *
     * @return array
     */
    public function getNormalizedImages(
        ProductInterface $product,
        $sku = '',
        $smallImageAttribute = '',
        $baseImageAttribute = '',
        $thumbnailAttribute = ''
    ) {
        $imageValues = $product->getValues()->filter(
            function ($value) {
                return $value->getData() instanceof AbstractProductMedia &&
                    in_array($value->getData()->getMimeType(), array('image/jpeg', 'image/png', 'image/gif'));
            }
        );

        if ($sku === '') {
            $sku = $product->getIdentifier();
        }

        $images = [];

        foreach ($imageValues as $imageValue) {
            $data = $imageValue->getData();

            if ($imageData = $this->mediaManager->getBase64($data)) {
                $imageTypes = array_merge(
                    $imageValue->getAttribute()->getCode() == $smallImageAttribute ? [Webservice::SMALL_IMAGE] : [],
                    $imageValue->getAttribute()->getCode() == $baseImageAttribute ? [Webservice::BASE_IMAGE] : [],
                    $imageValue->getAttribute()->getCode() == $thumbnailAttribute ? [Webservice::THUMBNAIL] : []
                );

                $images[] = [
                    (string) $sku,
                    [
                        'file' => [
                            'name'    => $data->getFilename(),
                            'content' => $imageData,
                            'mime'    => $data->getMimeType(),
                        ],
                        'label'    => $data->getFilename(),
                        'position' => 0,
                        'types'    => $imageTypes,
                        'exclude'  => 0,
                    ],
                    0,
                    'sku',
                ];
            }
        }

        return $images;
    }

    /**
     * Get the default product with all attributes (ie : event the non localizable ones).
     *
     * @param ProductInterface  $product                  The given product
     * @param array             $prestashopAttributes        Attribute list from Prestashop
     * @param array             $prestashopAttributesOptions Attribute options list from Prestashop
     * @param integer           $attributeSetId           Attribute set id
     * @param string            $defaultLocale            Default locale
     * @param string            $channel                  Channel
     * @param string            $website                  Website name
     * @param MappingCollection $categoryMapping          Root category mapping
     * @param MappingCollection $attributeMapping         Attribute mapping
     * @param string            $pimGrouped               Pim grouped association code
     * @param bool              $create                   Is it a creation ?
     * @param string            $defaultStoreValue        Default store value
     * @param boolean           $urlKey                   Product url key
     * @param boolean           $skuFirst                 Is sku first in url key?
     *
     * @return array The default product data
     */
    protected function getDefaultProduct(
        ProductInterface $product,
        $prestashopAttributes,
        $prestashopAttributesOptions,
        $attributeSetId,
        $defaultLocale,
        $channel,
        $website,
        MappingCollection $categoryMapping,
        MappingCollection $attributeMapping,
        $pimGrouped,
        $create,
        $defaultStoreValue,
        $urlKey = false,
        $skuFirst = false
    ) {
        $sku           = (string) $product->getIdentifier();
        $defaultValues = $this->getValues(
            $product,
            $prestashopAttributes,
            $prestashopAttributesOptions,
            $defaultLocale,
            $channel,
            $categoryMapping,
            $attributeMapping,
            false,
            $pimGrouped,
            $urlKey,
            $skuFirst
        );

        $defaultValues['websites'] = [$website];

        if ($create) {
            if ($this->hasGroupedProduct($product, $pimGrouped)) {
                $productType = self::PRESTASHOP_GROUPED_PRODUCT_KEY;
            } else {
                $productType = self::PRESTASHOP_SIMPLE_PRODUCT_KEY;
            }

            //For the default storeview we create an entire product
            $defaultProduct = [
                $productType,
                $attributeSetId,
                $sku,
                $defaultValues,
                $defaultStoreValue,
            ];
        } else {
            $defaultProduct = [
                $sku,
                $defaultValues,
                $defaultStoreValue,
                'sku',
            ];
        }

        return $defaultProduct;
    }

    /**
     * Test if a product has grouped products.
     *
     * @param ProductInterface $product
     * @param string           $pimGrouped
     *
     * @return boolean
     */
    protected function hasGroupedProduct(ProductInterface $product, $pimGrouped)
    {
        $association = $product->getAssociationForTypeCode($pimGrouped);

        return (null !== $association && count($association->getProducts()) > 0);
    }

    /**
     * Get values array for a given product.
     *
     * @param ProductInterface  $product                  The given product
     * @param array             $prestashopAttributes        Attribute list from Prestashop
     * @param array             $prestashopAttributesOptions Attribute options list from Prestashop
     * @param string            $localeCode               The locale to apply
     * @param string            $scopeCode                The Akeneo scope
     * @param MappingCollection $categoryMapping          Root category mapping
     * @param MappingCollection $attributeCodeMapping     Attribute mapping
     * @param boolean           $onlyLocalized            If true, only get translatable attributes
     * @param string            $pimGrouped               Pim grouped association code
     * @param boolean           $urlKey                   Product url key
     * @param boolean           $skuFirst                 Is sku first in url key?
     *
     * @return array Computed data
     */
    public function getValues(
        ProductInterface $product,
        $prestashopAttributes,
        $prestashopAttributesOptions,
        $localeCode,
        $scopeCode,
        MappingCollection $categoryMapping,
        MappingCollection $attributeCodeMapping,
        $onlyLocalized,
        $pimGrouped = null,
        $urlKey = false,
        $skuFirst = false
    ) {
        $normalizedValues = [];

        $context = [
            'identifier'               => $product->getIdentifier(),
            'scopeCode'                => $scopeCode,
            'localeCode'               => $localeCode,
            'onlyLocalized'            => $onlyLocalized,
            'prestashopAttributes'        => $prestashopAttributes,
            'prestashopAttributesOptions' => $prestashopAttributesOptions,
            'attributeCodeMapping'     => $attributeCodeMapping,
            'currencyCode'             => $this->currencyCode,
        ];

        foreach ($product->getValues() as $value) {
            $normalizedValue = $this->productValueNormalizer->normalize($value, 'PrestashopArray', $context);
            if ($normalizedValue !== null) {
                $normalizedValues = array_merge(
                    $normalizedValues,
                    $normalizedValue
                );
            }
        }

        $normalizedValues = array_merge(
            $normalizedValues,
            $this->getCustomValue(
                $product,
                $attributeCodeMapping,
                [
                    'categoryMapping' => $categoryMapping,
                    'scopeCode'       => $scopeCode,
                    'localeCode'      => $localeCode,
                    'pimGrouped'      => $pimGrouped,
                    'urlKey'          => $urlKey,
                    'skuFirst'        => $skuFirst,
                ]
            )
        );

        ksort($normalizedValues);

        return $normalizedValues;
    }

    /**
     * Get categories for the given product.
     *
     * @param ProductInterface  $product
     * @param MappingCollection $categoryMapping
     * @param string            $scopeCode
     *
     * @return array
     *
     * @throws CategoryNotFoundException
     */
    protected function getProductCategories(ProductInterface $product, MappingCollection $categoryMapping, $scopeCode)
    {
        $productCategories = [];

        $channelCategoryTree = $this->channelManager->getChannelByCode($scopeCode)->getCategory();

        foreach ($product->getCategories() as $category) {
            if ($category->getRoot() == $channelCategoryTree->getId()) {
                $prestashopCategoryId = $this->categoryMappingManager->getIdFromCategory(
                    $category,
                    $this->prestashopUrl,
                    $categoryMapping
                );

                if (!$prestashopCategoryId) {
                    throw new CategoryNotFoundException(
                        sprintf(
                            'The category %s was not found. Please export categories first or add it to the root '.
                            'category mapping',
                            $category->getLabel()
                        )
                    );
                }

                $productCategories[] = $prestashopCategoryId;
            }
        }

        return $productCategories;
    }

    /**
     * Get custom values (not provided by the PIM product).
     *
     * @param ProductInterface  $product
     * @param MappingCollection $attributeCodeMapping
     * @param array             $parameters
     *
     * @return array
     */
    protected function getCustomValue(
        ProductInterface $product,
        MappingCollection $attributeCodeMapping,
        array $parameters = []
    ) {
        if ($this->belongsToVariant($product) &&
            null !== $parameters['pimGrouped'] &&
            !$this->hasGroupedProduct($product, $parameters['pimGrouped'])) {
            $visibility = $this->variantMemberVisibility;
        } else {
            $visibility = $this->visibility;
        }

        $customValue = [
            strtolower($attributeCodeMapping->getTarget(self::VISIBILITY)) => $visibility,
            strtolower($attributeCodeMapping->getTarget(self::ENABLED))    => (string) ($this->enabled) ? 1 : 2,
            strtolower($attributeCodeMapping->getTarget('created_at'))     => $product->getCreated()
                ->format(AbstractNormalizer::DATE_FORMAT),
            strtolower($attributeCodeMapping->getTarget('updated_at'))     => $product->getUpdated()
                ->format(AbstractNormalizer::DATE_FORMAT),
            strtolower($attributeCodeMapping->getTarget('categories'))     => $this->getProductCategories(
                $product,
                $parameters['categoryMapping'],
                $parameters['scopeCode']
            ),
        ];

        if (false === $parameters['urlKey']) {
            $customValue[strtolower($attributeCodeMapping->getTarget(self::URL_KEY))] = $this->generateUrlKey(
                $product,
                $attributeCodeMapping,
                $parameters['localeCode'],
                $parameters['scopeCode'],
                $parameters['skuFirst']
            );
        }

        return $customValue;
    }

    /**
     * Check if the product belongs to a variant group.
     *
     * @param ProductInterface $product
     *
     * @return boolean
     */
    protected function belongsToVariant(ProductInterface $product)
    {
        foreach ($product->getGroups() as $group) {
            if ($group->getType()->isVariant()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate url key from product name and identifier.
     * The identifier is included to make sure the url_key is unique, as required in Prestashop.
     *
     * If name is localized, the default locale is used to get the value.
     *
     * @param ProductInterface  $product
     * @param MappingCollection $attributeCodeMapping
     * @param string            $localeCode
     * @param string            $scopeCode
     * @param boolean           $skuFirst
     *
     * @return string
     */
    protected function generateUrlKey(
        ProductInterface $product,
        MappingCollection $attributeCodeMapping,
        $localeCode,
        $scopeCode,
        $skuFirst = false
    ) {
        $identifier = $product->getIdentifier();
        $nameAttribute = $attributeCodeMapping->getSource(self::NAME);

        $name = $product->getValue($nameAttribute, $localeCode, $scopeCode);

        if (false === $skuFirst) {
            $url = Urlizer::urlize($name.'-'.$identifier);
        } else {
            $url = Urlizer::urlize($identifier.'-'.$name);
        }

        return $url;
    }
}
