<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Entity\Repository;

use Pim\Bundle\CatalogBundle\Entity\Repository\CategoryRepository as BaseCategoryRepository;
use Pim\Bundle\CatalogBundle\Model\CategoryInterface;

/**
 * Custom category repository.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
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
