<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Manager;

use Pim\Bundle\CatalogBundle\Manager\LocaleManager as BaseLocaleManager;

/**
 * @Deprecated
 *
 * Custom locale manager
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class LocaleManager
{
    /** @var BaseLocaleManager */
    protected $baseLocaleManager;

    /**
     * @param BaseLocaleManager $baseLocaleManager
     */
    public function __construct(BaseLocaleManager $baseLocaleManager)
    {
        $this->baseLocaleManager = $baseLocaleManager;
    }

    /**
     * @Deprecated
     *
     * Allow to list locales in an array like array[<code>] = <code>
     *
     * @return string[]
     */
    public function getLocaleChoices()
    {
        $codes = $this->baseLocaleManager->getActiveCodes();

        $choices = [];
        foreach ($codes as $code) {
            $choices[$code] = $code;
        }

        return $choices;
    }

    /**
     * Get active locales.
     *
     * @return \Pim\Bundle\CatalogBundle\Entity\Locale[]
     */
    public function getActiveLocales()
    {
        return $this->baseLocaleManager->getActiveLocales();
    }

    /**
     * @return \Pim\Bundle\CatalogBundle\Entity\Locale[]
     */
    public function getDisabledLocales()
    {
        return $this->baseLocaleManager->getDisabledLocales();
    }

    /**
     * Get locales with criteria.
     *
     * @param array $criteria
     *
     * @return \Pim\Bundle\CatalogBundle\Entity\Locale[]
     */
    public function getLocales($criteria = array())
    {
        return $this->baseLocaleManager->getLocales($criteria);
    }

    /**
     * @param string $code
     *
     * @return \Pim\Bundle\CatalogBundle\Entity\Locale
     */
    public function getLocaleByCode($code)
    {
        return $this->baseLocaleManager->getLocaleByCode($code);
    }

    /**
     * @return string[]
     */
    public function getActiveCodes()
    {
        return $this->baseLocaleManager->getActiveCodes();
    }
}
