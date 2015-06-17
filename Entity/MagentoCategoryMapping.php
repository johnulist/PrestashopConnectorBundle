<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Entity;

use Pim\Bundle\CatalogBundle\Model\CategoryInterface;

/**
 * Prestashop category mapping.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
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
