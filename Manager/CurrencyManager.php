<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Manager;

use Pim\Bundle\CatalogBundle\Manager\CurrencyManager as BaseCurrencyManager;

/**
 * @Deprecated
 *
 * Custom currency manager
 *
 */
class CurrencyManager
{
    /** @var BaseCurrencyManager */
    protected $baseCurrencyManager;

    /**
     * @param BaseCurrencyManager $baseCurrencyManager
     */
    public function __construct(BaseCurrencyManager $baseCurrencyManager)
    {
        $this->baseCurrencyManager = $baseCurrencyManager;
    }

    /**
     * @Deprecated
     *
     * Get active code choices
     *
     * Prior to PHP 5.4 array_combine() does not accept empty array as argument.
     * @see http://php.net/array_combine#refsect1-function.array-combine-changelog
     *
     * @return array
     */
    public function getActiveCodeChoices()
    {
        $codes = $this->baseCurrencyManager->getActiveCodes();
        if (empty($codes)) {
            return [];
        }

        return array_combine($codes, $codes);
    }

    /**
     * @Deprecated
     *
     * Get currency choices
     * Allow to list currencies in an array like array[<code>] = <code>
     *
     * @return string[]
     */
    public function getCurrencyChoices()
    {
        $currencyCodes = $this->getActiveCodeChoices();

        $choices = [];
        foreach ($currencyCodes as $code) {
            $choices[$code] = $code;
        }

        return $choices;
    }

    /**
     * Get active currencies.
     *
     * @return \Doctrine\Common\Persistence\mixed
     */
    public function getActiveCurrencies()
    {
        return $this->baseCurrencyManager->getActiveCurrencies();
    }

    /**
     * Get currencies with criteria.
     *
     * @param array $criteria
     *
     * @return \Doctrine\Common\Persistence\mixed
     */
    public function getCurrencies($criteria = array())
    {
        return $this->baseCurrencyManager->getCurrencies($criteria);
    }

    /**
     * Get active codes.
     *
     * @return string[]
     */
    public function getActiveCodes()
    {
        return $this->baseCurrencyManager->getActiveCodes();
    }
}
