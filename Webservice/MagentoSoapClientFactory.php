<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Webservice;

/**
 * A prestashop soap client factory.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
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
