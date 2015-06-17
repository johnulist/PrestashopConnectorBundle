<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Entity;

use Pim\Bundle\CatalogBundle\Entity\Family;

/**
 * Prestashop family mapping.
 *
 * @author    Olivier Soulet <olivier.soulet@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class PrestashopFamilyMapping
{
    /** @var integer */
    protected $id;

    /** @var string */
    protected $prestashopUrl;

    /** @var integer */
    protected $prestashopFamilyId;

    /** @var string */
    protected $pimFamilyCode;

    /** @var Family */
    protected $family;

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
     * @return PrestashopFamilyMapping
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
     * @param integer $prestashopFamilyId
     *
     * @return PrestashopFamilyMapping
     */
    public function setPrestashopFamilyId($prestashopFamilyId)
    {
        $this->prestashopFamilyId = $prestashopFamilyId;

        return $this;
    }

    /**
     * @return integer
     */
    public function getPrestashopFamilyId()
    {
        return $this->prestashopFamilyId;
    }

    /**
     * @param string $pimFamilyCode
     *
     * @return PrestashopFamilyMapping
     */
    public function setPimFamilyCode($pimFamilyCode)
    {
        $this->pimFamilyCode = $pimFamilyCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getPimFamilyCode()
    {
        return $this->pimFamilyCode;
    }

    /**
     * @param Family $family
     *
     * @return PrestashopFamilyMapping
     */
    public function setFamily(Family $family)
    {
        $this->family = $family;

        return $this;
    }

    /**
     * @return Family
     */
    public function getFamily()
    {
        return $this->family;
    }
}
