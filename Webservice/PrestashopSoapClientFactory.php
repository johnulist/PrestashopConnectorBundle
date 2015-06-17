<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Webservice;

/**
 * A prestashop soap client factory.
 *
 */
class PrestashopSoapClientFactory
{
    /* @var PrestashopSoapClientProfiler */
    protected $profiler;

    /**
     * Get a new prestashop soap client.
     *
     * @param PrestashopSoapClientParameters $clientParameters
     *
     * @return PrestashopSoapClient
     */
    public function getPrestashopSoapClient(PrestashopSoapClientParameters $clientParameters)
    {
        return new PrestashopSoapClient($clientParameters, null, $this->profiler);
    }

    /**
     * Set PrestashopSoapClientProfiler.
     *
     * @param PrestashopSoapClientProfiler $profiler
     */
    public function setProfiler(PrestashopSoapClientProfiler $profiler)
    {
        $this->profiler = $profiler;
    }
}
