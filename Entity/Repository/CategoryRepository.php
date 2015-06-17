<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Entity\Repository;

use Pim\Bundle\CatalogBundle\Entity\Repository\CategoryRepository as BaseCategoryRepository;
use Pim\Bundle\CatalogBundle\Model\CategoryInterface;

/**
 * Custom category repository.
 *
 */
class CategoryRepository extends BaseCategoryRepository
{
    /**
     * Get all categories in order.
     *
     * @return array
     */
    public function findOrderedCategories(CategoryInterface $rootCategory)
    {
        return $this
            ->createQueryBuilder('c')
            ->select('c')
            ->andWhere('c.root = '.$rootCategory->getId())
            ->orderBy('c.level, c.left', 'ASC');
    }
}
