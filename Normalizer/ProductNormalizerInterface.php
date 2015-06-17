<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Normalizer;

use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use Pim\Bundle\PrestashopConnectorBundle\Mapper\MappingCollection;

/**
 * Defines the interface of a product normalizers.
 *
 */
interface ProductNormalizerInterface
{
    /**
     * Get values array for a given product.
     *
     * @param ProductInterface  $product                  The given product
     * @param array             $prestashopAttributes        Attribute list from Prestashop
     * @param array             $prestashopAttributesOptions Attribute options list from Prestashop
     * @param string            $localeCode               The locale to apply
     * @param string            $scopeCode                The akeno scope
     * @param MappingCollection $categoryMapping          Root category mapping
     * @param MappingCollection $attributeMapping         Attribute mapping
     * @param boolean           $onlyLocalized            If true, only get translatable attributes
     * @param string            $pimGrouped               Pim grouped association code
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
        MappingCollection $attributeMapping,
        $onlyLocalized,
        $pimGrouped
    );

    /**
     * Get all images of a product normalized.
     *
     * @param ProductInterface $product
     *
     * @return array
     */
    public function getNormalizedImages(ProductInterface $product);
}
