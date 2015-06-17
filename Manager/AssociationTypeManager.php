<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * Association type manager.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class AssociationTypeManager
{
    /** @var \Doctrine\Common\Persistence\ObjectManager */
    protected $objectManager;

    /** @var string */
    protected $className;

    /**
     * @param ObjectManager $objectManager
     * @param string        $className
     */
    public function __construct(ObjectManager $objectManager, $className)
    {
        $this->objectManager = $objectManager;
        $this->className     = $className;
    }

    /**
     * Get association types with criteria.
     *
     * @param string[] $criteria
     *
     * @return \Pim\Bundle\CatalogBundle\Entity\AssociationType[]|null
     */
    public function getAssociationTypes($criteria = [])
    {
        return $this->getEntityRepository()->findBy($criteria);
    }

    /**
     * Get association types with criteria.
     *
     * @param string[] $criteria
     *
     * @return \Pim\Bundle\CatalogBundle\Entity\AssociationType|null
     */
    public function getAssociationType($criteria = [])
    {
        return $this->getEntityRepository()->findOneBy($criteria);
    }

    /**
     * Get association types by code.
     *
     * @param string $code
     *
     * @return array
     */
    public function getAssociationTypeByCode($code)
    {
        return $this->getAssociationType(['code' => $code]);
    }

    /**
     * Get association type choices with criteria
     * Allow to list association types in an array like array[<code>] = <label>.
     *
     * @param array $criteria
     *
     * @return string[]
     */
    public function getAssociationTypeChoices($criteria = [])
    {
        $associationTypes = $this->getAssociationTypes($criteria);

        $choices = [];

        foreach ($associationTypes as $associationType) {
            $choices[$associationType->getCode()] = $associationType->getLabel();
        }

        return $choices;
    }

    /**
     * Get the entity manager.
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getEntityRepository()
    {
        return $this->objectManager->getRepository($this->className);
    }
}
