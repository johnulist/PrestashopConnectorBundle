<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Manager;

use Pim\Bundle\CatalogBundle\Model\AbstractAttribute;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Attribute mapping manager.
 *
 */
class AttributeMappingManager
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
     * Get attribute from id and Prestashop url.
     *
     * @param integer $id
     * @param string  $prestashopUrl
     *
     * @return AbstractAttribute|null
     */
    public function getAttributeFromId($id, $prestashopUrl)
    {
        $prestashopAttributeMapping = $this->getEntityRepository()->findOneBy(
            [
                'prestashopAttributeId' => $id,
                'prestashopUrl'         => $prestashopUrl,
            ]
        );

        return $prestashopAttributeMapping ? $prestashopAttributeMapping->getAttribute() : null;
    }

    /**
     * Get id from attribute and Prestashop url.
     *
     * @param AbstractAttribute $attribute
     * @param string            $prestashopUrl
     *
     * @return integer|null
     */
    public function getIdFromAttribute(AbstractAttribute $attribute, $prestashopUrl)
    {
        $attributeMapping = $this->getEntityRepository()->findOneBy(
            [
                'attribute'   => $attribute,
                'prestashopUrl'  => $prestashopUrl,
            ]
        );

        return $attributeMapping ? $attributeMapping->getPrestashopAttributeId() : null;
    }

    /**
     * Get all attribute mapping for a given prestashop.
     *
     * @param string $prestashopUrl
     *
     * @return array
     */
    public function getAllPrestashopAttribute($prestashopUrl)
    {
        return $this->getEntityRepository()->findAll(
            [
                'prestashopUrl' => $prestashopUrl,
            ]
        );
    }

    /**
     * Register a new attribute mapping.
     *
     * @param AbstractAttribute $pimAttribute
     * @param integer           $prestashopAttributeId
     * @param string            $prestashopUrl
     */
    public function registerAttributeMapping(
        AbstractAttribute $pimAttribute,
        $prestashopAttributeId,
        $prestashopUrl
    ) {
        $attributeMapping = $this->getEntityRepository()->findOneBy([
            'attribute'  => $pimAttribute,
            'prestashopUrl' => $prestashopUrl,
        ]);
        $prestashopAttributeMapping = new $this->className();

        if ($attributeMapping) {
            $prestashopAttributeMapping = $attributeMapping;
        }

        $prestashopAttributeMapping->setAttribute($pimAttribute);
        $prestashopAttributeMapping->setPrestashopAttributeId($prestashopAttributeId);
        $prestashopAttributeMapping->setPrestashopUrl($prestashopUrl);

        $this->objectManager->persist($prestashopAttributeMapping);
        $this->objectManager->flush();
    }

    /**
     * Does the given prestashop attribute exist in pim ?
     *
     * @param string $attributeId
     * @param string $prestashopUrl
     *
     * @return boolean
     */
    public function prestashopAttributeExists($attributeId, $prestashopUrl)
    {
        return $this->getAttributeFromId($attributeId, $prestashopUrl) !== null;
    }

    /**
     * Get the entity repository.
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getEntityRepository()
    {
        return $this->objectManager->getRepository($this->className);
    }
}
