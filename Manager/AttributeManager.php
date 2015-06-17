<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Pim\Bundle\CatalogBundle\Manager\AttributeManager as BaseAttributeManager;
use Pim\Bundle\CatalogBundle\Entity\Repository\AttributeRepository;
use Pim\Bundle\CatalogBundle\Model\AbstractAttribute;

/**
 * @Deprecated
 *
 * Custom attribute manager
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class AttributeManager
{
    /** @var BaseAttributeManager */
    protected $baseAttributeManager;

    /** @var ObjectManager */
    protected $objectManager;

    /** @var string */
    protected $attributeClass;

    /**
     * @param BaseAttributeManager $baseAttributeManager
     * @param ObjectManager        $objectManager
     * @param string               $attributeClass
     */
    public function __construct(
        BaseAttributeManager $baseAttributeManager,
        ObjectManager $objectManager,
        $attributeClass
    ) {
        $this->baseAttributeManager = $baseAttributeManager;
        $this->objectManager        = $objectManager;
        $this->attributeClass       = $attributeClass;
    }

    /**
     * @Deprecated
     *
     * @param array $criteria
     *
     * @return \Pim\Bundle\CatalogBundle\Entity\Attribute[]
     */
    public function getAttributes(array $criteria = [])
    {
        return $this->getRepository()->findBy($criteria);
    }

    /**
     * @Deprecated
     *
     * @return array
     */
    public function getImageAttributeChoice()
    {
        $imageAttributes = $this->getAttributes(['attributeType' => 'pim_catalog_image']);

        $result = [];

        foreach ($imageAttributes as $attribute) {
            $result[$attribute->getCode()] = $attribute->getLabel();
        }

        return $result;
    }

    /**
     * Create an attribute.
     *
     * @param string $type
     *
     * @return AbstractAttribute
     */
    public function createAttribute($type = null)
    {
        return $this->baseAttributeManager->createAttribute($type);
    }

    /**
     * Create an attribute option.
     *
     * @return \Pim\Bundle\CatalogBundle\Entity\AttributeOption
     */
    public function createAttributeOption()
    {
        return $this->baseAttributeManager->createAttributeOption();
    }

    /**
     * Create an attribute option value.
     *
     * @return \Pim\Bundle\CatalogBundle\Entity\AttributeOptionValue
     */
    public function createAttributeOptionValue()
    {
        return $this->baseAttributeManager->createAttributeOptionValue();
    }

    /**
     * Get the attribute FQCN.
     *
     * @return string
     */
    public function getAttributeClass()
    {
        return $this->baseAttributeManager->getAttributeClass();
    }

    /**
     * Get the attribute option FQCN.
     *
     * @return string
     */
    public function getAttributeOptionClass()
    {
        return $this->baseAttributeManager->getAttributeOptionClass();
    }

    /**
     * Get a list of available attribute types.
     *
     * @return string[]
     */
    public function getAttributeTypes()
    {
        return $this->baseAttributeManager->getAttributeTypes();
    }

    /**
     * Remove an attribute.
     *
     * @param AbstractAttribute $attribute
     */
    public function remove(AbstractAttribute $attribute)
    {
        $this->baseAttributeManager->remove($attribute);
    }

    /**
     * @Deprecated
     *
     * Returns the entity repository
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getRepository()
    {
        $classMetadata = $this->objectManager->getMetadataFactory()->getMetadataFor($this->attributeClass);

        return new AttributeRepository($this->objectManager, $classMetadata);
    }
}
