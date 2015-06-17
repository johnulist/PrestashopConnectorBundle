<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Entity;

use Pim\Bundle\CatalogBundle\Model\AbstractAttribute;

/**
 * Prestashop attribute mapping.
 *
 */
class PrestashopAttributeMapping
{
    /** @var integer */
    protected $id;

    /** @var string */
    protected $prestashopUrl;

    /** @var integer */
    protected $prestashopAttributeId;

    /** @var AbstractAttribute */
    protected $attribute;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $prestashopUrl
     *
     * @return PrestashopAttributeMapping
     */
    public function setPrestashopUrl($prestashopUrl)
    {
        $this->prestashopUrl = $prestashopUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrestashopUrl()
    {
        return $this->prestashopUrl;
    }

    /**
     * @param string $prestashopAttributeId
     *
     * @return PrestashopAttributeMapping
     */
    public function setPrestashopAttributeId($prestashopAttributeId)
    {
        $this->prestashopAttributeId = $prestashopAttributeId;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrestashopAttributeId()
    {
        return $this->prestashopAttributeId;
    }

    /**
     * @param AbstractAttribute $attribute
     *
     * @return PrestashopAttributeMapping
     */
    public function setAttribute(AbstractAttribute $attribute)
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * @return AbstractAttribute
     */
    public function getAttribute()
    {
        return $this->attribute;
    }
}
