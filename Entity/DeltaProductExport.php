<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Entity;

use Akeneo\Bundle\BatchBundle\Entity\JobInstance;

/**
 * Delta product export entity.
 *
 */
class DeltaProductExport
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
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \DateTime $lastExport
     *
     * @return DeltaProductExport
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
     * @return DeltaProductExport
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
     * @return DeltaProductExport
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
