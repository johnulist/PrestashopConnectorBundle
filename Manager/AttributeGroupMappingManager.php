<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Pim\Bundle\CatalogBundle\Entity\AttributeGroup;
use Pim\Bundle\CatalogBundle\Entity\Family;

/**
 * Attribute group mapping manager.
 *
 */
class AttributeGroupMappingManager
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
     * Get id from group and Prestashop url.
     *
     * @param AttributeGroup $group
     * @param Family         $family
     * @param string         $prestashopUrl
     *
     * @return integer
     */
    public function getIdFromGroup(AttributeGroup $group, Family $family, $prestashopUrl)
    {
        $groupMapping = $this->getEntityRepository()->findOneBy(
            [
                'pimGroupCode'  => $group->getCode(),
                'pimFamilyCode' => $family->getCode(),
                'prestashopUrl'    => $prestashopUrl,
            ]
        );

        return $groupMapping ? $groupMapping->getPrestashopGroupId() : null;
    }

    /**
     * Register a new group mapping.
     *
     * @param AttributeGroup $pimGroup
     * @param Family         $pimFamily
     * @param integer        $prestashopGroupId
     * @param string         $prestashopUrl
     */
    public function registerGroupMapping(
        AttributeGroup $pimGroup,
        Family $pimFamily,
        $prestashopGroupId,
        $prestashopUrl
    ) {
        $groupMapping = $this->getEntityRepository()->findOneBy(
            [
                'pimGroupCode'  => $pimGroup->getCode(),
                'pimFamilyCode' => $pimFamily->getCode(),
                'prestashopUrl'    => $prestashopUrl,
            ]
        );

        $prestashopGroupMapping = new $this->className();

        if ($groupMapping) {
            $prestashopGroupMapping = $groupMapping;
        }

        $prestashopGroupMapping->setPimGroupCode($pimGroup->getCode());
        $prestashopGroupMapping->setPimFamilyCode($pimFamily->getCode());
        $prestashopGroupMapping->setPrestashopGroupId($prestashopGroupId);
        $prestashopGroupMapping->setPrestashopUrl($prestashopUrl);

        $this->objectManager->persist($prestashopGroupMapping);
        $this->objectManager->flush();
    }

    /**
     * @return array
     */
    public function getAllMappings()
    {
        return ($this->getEntityRepository()->findAll() ? $this->getEntityRepository()->findAll() : null);
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
