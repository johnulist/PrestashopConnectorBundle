<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Entity\Repository;

use Pim\Bundle\CatalogBundle\Entity\Repository\CurrencyRepository as BaseCurrencyRepository;

/**
 * Custom currency repository.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
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
