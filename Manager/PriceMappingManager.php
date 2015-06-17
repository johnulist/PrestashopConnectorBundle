<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Manager;

use Pim\Bundle\CatalogBundle\Entity\Group;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use Pim\Bundle\CatalogBundle\Model\AbstractAttribute;
use Pim\Bundle\CatalogBundle\Entity\AttributeOption;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\ConfigurableNormalizer;
use Pim\Bundle\PrestashopConnectorBundle\Mapper\MappingCollection;

/**
 * Price mapping manager.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class PriceMappingManager
{
    /** @staticvar string Prestashop price attribute */
    const MAGENTO_PRICE = 'price';

    /** @var string */
    protected $locale;

    /** @var string */
    protected $currency;

    /** @var string */
    protected $channel;

    /**
     * @param string $locale
     * @param string $currency
     * @param string $channel
     */
    public function __construct($locale, $currency, $channel)
    {
        $this->locale   = $locale;
        $this->currency = $currency;
        $this->channel  = $channel;
    }

    /**
     * Get price mapping for the given group and products.
     *
     * @param Group             $group
     * @param array             $products
     * @param MappingCollection $attributeMapping
     * @param boolean           $lowest
     *
     * @return array
     */
    public function getPriceMapping(Group $group, array $products, MappingCollection $attributeMapping, $lowest = true)
    {
        $attributes = $group->getAttributes();
        $basePrice  = $this->getLimitPrice($products, $attributeMapping, [], $lowest);

        $sortedAttributes = $this->getSortedAttributes($attributes, $products, $basePrice, $attributeMapping, $lowest);

        $priceChanges = [];

        foreach ($sortedAttributes as $attribute) {
            $attributePriceMapping = $this->getAttributePriceMapping(
                $attribute,
                $basePrice,
                $products,
                $priceChanges,
                $attributeMapping,
                $lowest
            );

            $priceChanges[$attributeMapping->getTarget($attribute->getCode())] = $attributePriceMapping;
        }

        if ($lowest) {
            try {
                $this->validatePriceMapping($products, $priceChanges, $basePrice, $attributeMapping);
            } catch (ComputedPriceNotMatchedException $e) {
                return $this->getPriceMapping($group, $products, $attributeMapping, false);
            }
        }

        ksort($priceChanges);

        return [
            ConfigurableNormalizer::PRICE_CHANGES => $priceChanges,
            ConfigurableNormalizer::BASE_PRICE    => $basePrice,
        ];
    }

    /**
     * Get the limit price of given products.
     *
     * @param array             $products
     * @param MappingCollection $attributeMapping
     * @param array             $priceChanges
     * @param boolean           $lowest
     *
     * @return int
     */
    public function getLimitPrice(
        array $products,
        MappingCollection $attributeMapping,
        array $priceChanges = [],
        $lowest = true
    ) {
        $limitPrice = $this->getProductPrice($products[0], $attributeMapping, $priceChanges, $lowest);

        foreach ($products as $product) {
            $productPrice = $this->getProductPrice($product, $attributeMapping, $priceChanges, $lowest);

            if ($lowest) {
                $limitPrice = ($productPrice < $limitPrice) ? $productPrice : $limitPrice;
            } else {
                $limitPrice = ($productPrice > $limitPrice) ? $productPrice : $limitPrice;
            }
        }

        return $limitPrice;
    }

    /**
     * Get sorted attributes for mapping.
     *
     * @param \Doctrine\Common\Collections\ArrayCollection|array $attributes
     * @param array                                              $products
     * @param float                                              $basePrice
     * @param MappingCollection                                  $attributeMapping
     * @param boolean                                            $lowest
     *
     * @return array
     */
    protected function getSortedAttributes(
        $attributes,
        array $products,
        $basePrice,
        MappingCollection $attributeMapping,
        $lowest
    ) {
        $attributeDelta = [];
        $attributeMap   = [];

        foreach ($attributes as $attribute) {
            $absoluteAttributeMapping = $this->getAttributePriceMapping(
                $attribute,
                $basePrice,
                $products,
                [],
                $attributeMapping,
                $lowest
            );

            if (!empty($absoluteAttributeMapping)) {
                $attributeDelta[$attributeMapping->getTarget($attribute->getCode())] = max($absoluteAttributeMapping);
            }
            $attributeMap[$attributeMapping->getTarget($attribute->getCode())]   = $attribute;
        }

        asort($attributeDelta);

        array_walk(
            $attributeDelta,
            function (&$value, $key) use ($attributeMap) {
                $value = $attributeMap[$key];
            }
        );

        return $attributeDelta;
    }

    /**
     * Get the price of the given product.
     *
     * @param ProductInterface  $product
     * @param MappingCollection $attributeMapping
     * @param array             $priceChanges
     * @param boolean           $lowest
     *
     * @return int
     */
    protected function getProductPrice(
        ProductInterface $product,
        MappingCollection $attributeMapping,
        array $priceChanges = [],
        $lowest = true
    ) {
        $toSubstract = 0;

        foreach ($priceChanges as $attributeCode => $attributePriceMapping) {
            $attributeCode = $attributeMapping->getSource($attributeCode);
            foreach ($attributePriceMapping as $optionCode => $optionPrice) {
                if ($product->getValue($attributeCode, $this->locale) !== null &&
                    $product->getValue($attributeCode, $this->locale)->getData()->getCode() === $optionCode
                ) {
                    $toSubstract += $optionPrice;
                }
            }
        }

        $toSubstract = ($lowest * -1) * $toSubstract;

        $priceAttr = $attributeMapping->getSource(self::MAGENTO_PRICE);

        $price = $product->getValue($priceAttr, $this->locale, $this->channel);

        $data = (null != $price) ? $price->getPrice($this->currency)->getData() : 0;

        return $data + $toSubstract;
    }

    /**
     * Get price mapping for an attribute.
     *
     * @param AbstractAttribute $attribute
     * @param int               $basePrice
     * @param array             $products
     * @param array             $priceChanges
     * @param MappingCollection $attributeMapping
     * @param boolean           $lowest
     *
     * @return array
     */
    protected function getAttributePriceMapping(
        AbstractAttribute $attribute,
        $basePrice,
        array $products,
        array $priceChanges,
        MappingCollection $attributeMapping,
        $lowest
    ) {
        $attributePriceMapping = [];

        foreach ($attribute->getOptions() as $option) {
            $productsWithOption = $this->getProductsWithOption($products, $option);

            if (count($productsWithOption) > 0) {
                $priceDiff = $this->getLimitPrice(
                    $productsWithOption,
                    $attributeMapping,
                    $priceChanges,
                    $lowest
                ) - $basePrice;

                $attributePriceMapping[$option->getCode()] = $priceDiff;
            }
        }

        return $attributePriceMapping;
    }

    /**
     * Get all products with the given option value.
     *
     * @param ProductInterface[] $products
     * @param AttributeOption    $option
     *
     * @return array
     */
    protected function getProductsWithOption(array $products, AttributeOption $option)
    {
        $productsWithOption = [];
        $attributeCode      = $option->getAttribute()->getCode();

        //PHP Warning:  max(): Array must contain at least one element in /home/akeneo_pim/pim-natalys/vendor/akeneo/prestashop-connector-bundle/Pim/Bundle/PrestashopConnectorBundle/Manager/PriceMappingManager.php on line 156

        foreach ($products as $product) {
            if ($product->getValue($attributeCode, $this->locale) !== null &&
                $product->getValue($attributeCode, $this->locale)->getData() !== null &&
                $product->getValue($attributeCode, $this->locale)->getData()->getCode() === $option->getCode()
            ) {
                $productsWithOption[] = $product;
            }
        }

        return $productsWithOption;
    }

    /**
     * Validate generated price mapping.
     *
     * @param array             $products
     * @param array             $priceChanges
     * @param float             $basePrice
     * @param MappingCollection $attributeMapping
     *
     * @return boolean
     */
    public function validatePriceMapping(
        array $products,
        array $priceChanges,
        $basePrice,
        MappingCollection $attributeMapping
    ) {
        foreach ($products as $product) {
            $productPrice            = $this->getProductPrice($product, $attributeMapping);
            $productPriceFromMapping = $this->getProductPriceFromMapping(
                $product,
                $priceChanges,
                $basePrice,
                $attributeMapping
            );

            if ($productPrice != $productPriceFromMapping) {
                throw new ComputedPriceNotMatchedException(
                    sprintf(
                        "Computed price mapping : %s. \n".
                        "Base price : %s %s. \n".
                        "Item causing the problem : %s. \n".
                        "Actual product price : %s %s. \n".
                        "Computed product price from mapping : %s %s.",
                        json_encode($priceChanges),
                        $basePrice,
                        $this->currency,
                        $product->getIdentifier(),
                        $productPrice,
                        $this->currency,
                        $productPriceFromMapping,
                        $this->currency
                    )
                );
            }
        }
    }

    /**
     * Get product price from generated mapping.
     *
     * @param ProductInterface  $product
     * @param array             $priceChanges
     * @param float             $basePrice
     * @param MappingCollection $attributeMapping
     *
     * @return float
     */
    protected function getProductPriceFromMapping(
        ProductInterface $product,
        array $priceChanges,
        $basePrice,
        MappingCollection $attributeMapping
    ) {
        $priceFromMapping = $basePrice;

        foreach ($priceChanges as $attributeCode => $attributePriceMapping) {
            $priceFromMapping += $this->getAttributePriceFromMapping(
                $product,
                $attributeMapping->getSource($attributeCode),
                $attributePriceMapping
            );
        }

        return $priceFromMapping;
    }

    /**
     * Get the attribute price from generated mapping.
     *
     * @param ProductInterface $product
     * @param string           $attributeCode
     * @param array            $attributeMapping
     *
     * @return float
     */
    protected function getAttributePriceFromMapping(ProductInterface $product, $attributeCode, array $attributeMapping)
    {
        if ($product->getValue($attributeCode, $this->locale) !== null) {
            foreach ($attributeMapping as $optionCode => $optionPrice) {
                if ($this->doesOptionCodesMatch(
                        $product->getValue($attributeCode, $this->locale)->getData()->getCode(),
                        $optionCode
                )) {
                    return $optionPrice;
                }
            }
        }

        return 0;
    }

    /**
     * @param string $productOptionCode
     * @param mixed  $mappingOptionCode
     *
     * @return boolean
     */
    protected function doesOptionCodesMatch($productOptionCode, $mappingOptionCode)
    {
        if (is_numeric($productOptionCode)) {
            return ((int) $productOptionCode) === $mappingOptionCode;
        }

        return $productOptionCode === $mappingOptionCode;
    }
}
