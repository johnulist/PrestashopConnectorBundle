<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Entity\Repository;

use Pim\Bundle\CatalogBundle\Entity\Repository\CurrencyRepository as BaseCurrencyRepository;

/**
 * Custom currency repository.
 *
 */
class CurrencyRepository extends BaseCurrencyRepository
{
    /**
     * Get all categories for the given criteria.
     *
     * @param array $criteria
     *
     * @return \Pim\Bundle\CatalogBundle\Entity\Currency[]
     */
    public function getCategories(array $criteria)
    {
        return $this->findBy($criteria);
    }
}
