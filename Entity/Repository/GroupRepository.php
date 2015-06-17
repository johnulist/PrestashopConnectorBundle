<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Entity\Repository;

use Pim\Bundle\CatalogBundle\Entity\Repository\GroupRepository as BaseGroupRepository;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\Webservice;

/**
 * Custom group repository.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class GroupRepository extends BaseGroupRepository
{
    /** @staticvar string */
    const VARIANT_GROUP_CODE = 'VARIANT';

    /**
     * Get all variant groups ids.
     *
     * @return array
     */
    public function getVariantGroupIds()
    {
        $variantGroups = $this->getVariantGroupsQb()
            ->select('g.id')
            ->getQuery()
            ->getResult();

        array_walk(
            $variantGroups,
            function (&$value) {
                $value = $value['id'];
            }
        );

        return $variantGroups;
    }

    /**
     * Get all variant groups ids.
     *
     * @return array
     */
    public function getVariantGroupSkus()
    {
        $variantGroups = $this->getVariantGroupsQb()
            ->select('g.code')
            ->getQuery()
            ->getResult();

        array_walk(
            $variantGroups,
            function (&$value) {
                $value = sprintf(Webservice::CONFIGURABLE_IDENTIFIER_PATTERN, $value['code']);
            }
        );

        return $variantGroups;
    }

    /**
     * Get variant group query builder.
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getVariantGroupsQb()
    {
        return $this
            ->createQueryBuilder('g')
            ->leftJoin('g.type', 't')
            ->andWhere('t.code = :variant_code')
            ->setParameter(':variant_code', self::VARIANT_GROUP_CODE);
    }

    /**
     * Get axis attributes.
     *
     * @return array
     */
    public function getAxisAttributes()
    {
        return $this
            ->createQueryBuilder('g')
            ->select('DISTINCT(a.code) as code')
            ->leftJoin('g.type', 't', \Doctrine\ORM\Query\Expr\Join::WITH, 'g.code = :variant')
            ->setParameter(':variant', 'VARIANT')
            ->leftJoin('g.attributes', 'a')
            ->getQuery()
            ->getArrayResult();
    }
}
