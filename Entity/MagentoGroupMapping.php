<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Entity;

/**
 * Prestashop group mapping.
 *
 * @author    Olivier Soulet <olivier.soulet@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class PrestashopGroupMapping
{
    /** @var integer */
    protected $id;

    /** @var string */
    protected $prestashopUrl;

    /** @var integer */
    protected $prestashopGroupId;

    /** @var string */
    protected $pimGroupCode;

    /** @var integer */
    protected $pimFamilyCode;

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
     * @return PrestashopGroupMapping
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
     * @param string $prestashopGroupId
     *
     * @return PrestashopGroupMapping
     */
    public function setPrestashopGroupId($prestashopGroupId)
    {
        $this->prestashopGroupId = $prestashopGroupId;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrestashopGroupId()
    {
        return $this->prestashopGroupId;
    }

    /**
     * @param string $pimGroupCode
     *
     * @return PrestashopGroupMapping
     */
    public function setPimGroupCode($pimGroupCode)
    {
        $this->pimGroupCode = $pimGroupCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getPimGroupCode()
    {
        return $this->pimGroupCode;
    }

    /**
     * @param integer $pimFamilyCode
     *
     * @return PrestashopGroupMapping
     */
    public function setPimFamilyCode($pimFamilyCode)
    {
        $this->pimFamilyCode = $pimFamilyCode;

        return $this;
    }

    /**
     * @return integer
     */
    public function getPimFamilyCode()
    {
        return $this->pimFamilyCode;
    }
}
