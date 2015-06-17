<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Normalizer;

use Pim\Bundle\PrestashopConnectorBundle\Normalizer\Exception\LocaleNotMatchedException;
use Pim\Bundle\PrestashopConnectorBundle\Mapper\MappingCollection;
use Pim\Bundle\CatalogBundle\Entity\Locale;

/**
 * A normalizer to transform a product entity into an array for Prestashop platform above 1.6.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductNormalizer16 extends ProductNormalizer implements ProductNormalizerInterface
{
    /**
     * Get the corresponding storeview code for a givent locale.
     *
     * @param string            $locale
     * @param array             $prestashopStoreViews
     * @param MappingCollection $storeViewMapping
     *
     * @return string
     */
    protected function getStoreViewForLocale($locale, $prestashopStoreViews, MappingCollection $storeViewMapping)
    {
        return ['code' => $storeViewMapping->getTarget($locale)];
    }

    /**
     * Manage not found locales.
     *
     * @param Locale $locale
     *
     * @throws LocaleNotMatchedException
     */
    protected function localeNotFound(Locale $locale)
    {
        throw new LocaleNotMatchedException(
            sprintf(
                'No storeview found for the locale "%s". Please map the locale "%s" to a Prestashop storeview',
                $locale->getCode(),
                $locale->getCode()
            )
        );
    }
}
