<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Normalizer;

use Pim\Bundle\CatalogBundle\Entity\AttributeOption;

/**
 * A normalizer to transform a option entity into an array.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class OptionNormalizer extends AbstractNormalizer
{
    /**
     * {@inheritdoc}
     */
    public function normalize($attributeOption, $format = null, array $context = [])
    {
        $label = [
            [
                'store_id' => '0',
                'value'    => $attributeOption->getCode(),
            ],
            [
                'store_id' => '1',
                'value'    => $this->getOptionLabel($attributeOption, $context['defaultLocale']),
            ],
        ];

        foreach ($this->getOptionLocales($attributeOption) as $locale) {
            $storeView = $this->getStoreViewForLocale(
                $locale,
                $context['prestashopStoreViews'],
                $context['storeViewMapping']
            );

            if ($storeView) {
                $label[] = [
                    'store_id' => (string) $storeView['store_id'],
                    'value'    => $this->getOptionLabel(
                        $attributeOption,
                        $locale
                    ),
                ];
            }
        }

        return [
            $context['attributeCode'],
            [
                'label'      => $label,
                'order'      => $attributeOption->getSortOrder(),
            ],
        ];
    }

    /**
     * get options locale.
     *
     * @param AttributeOption $option
     *
     * @return array
     */
    protected function getOptionLocales(AttributeOption $option)
    {
        $locales = [];

        foreach ($option->getOptionValues() as $optionValue) {
            $locales[] = $optionValue->getLocale();
        }

        return $locales;
    }

    /**
     * Get option translation for given locale code.
     *
     * @param AttributeOption $option
     * @param string          $locale
     *
     * @return mixed
     */
    protected function getOptionLabel(AttributeOption $option, $locale)
    {
        $optionValue = $option->setLocale($locale)->getOptionValue();

        return $optionValue ? $optionValue->getLabel() : $option->getCode();
    }
}
