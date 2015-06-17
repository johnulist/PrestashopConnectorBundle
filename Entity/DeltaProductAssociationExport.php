<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Entity;

use Akeneo\Bundle\BatchBundle\Entity\JobInstance;

/**
 * Delta product association export entity.
 *
 */
class DeltaProductAssociationExport
{
    /** @var int */
    protected $id;

    /** @var \DateTime */
    protected $lastExport;

    /** @var string|int */
    protected $productId;

    /** @var JobInstance */
    protected $jobInstance;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \DateTime $lastExport
     *
     * @return DeltaProductAssociationExport
     */
    public function setLastExport(\DateTime $lastExport)
    {
        $this->lastExport = $lastExport;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastExport()
    {
        return $this->lastExport;
    }

    /**
     * @param string|int $productId
     *
     * @return DeltaProductAssociationExport
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;

        return $this;
    }

    /**
     * @return string|int
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @param JobInstance $jobInstance
     *
     * @return DeltaProductAssociationExport
     */
    public function setJobInstance(JobInstance $jobInstance = null)
    {
        $this->jobInstance = $jobInstance;

        return $this;
    }

    /**
     * @return JobInstance
     */
    public function getJobInstance()
    {
        return $this->jobInstance;
    }
}
