<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Entity;

use Pim\Bundle\CatalogBundle\Model\CategoryInterface;

/**
 * Prestashop category mapping.
 *
 */
class PrestashopCategoryMapping
{
    /** @var integer */
    protected $id;

    /** @var string */
    protected $prestashopUrl;

    /** @var integer */
    protected $prestashopCategoryId;

    /** @var CategoryInterface */
    protected $category;

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
     * @return PrestashopCategoryMapping
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
     * @param string $prestashopCategoryId
     *
     * @return PrestashopCategoryMapping
     */
    public function setPrestashopCategoryId($prestashopCategoryId)
    {
        $this->prestashopCategoryId = $prestashopCategoryId;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrestashopCategoryId()
    {
        return $this->prestashopCategoryId;
    }

    /**
     * @param CategoryInterface $category
     *
     * @return PrestashopCategoryMapping
     */
    public function setCategory(CategoryInterface $category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return CategoryInterface
     */
    public function getCategory()
    {
        return $this->category;
    }
}
