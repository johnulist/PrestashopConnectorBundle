<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Guesser;

use Pim\Bundle\PrestashopConnectorBundle\Webservice\Webservice;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\Webservice16;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\WebserviceEE;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopRestClientParameters;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopSoapClientFactory;

/**
 * A prestashop soap client to abstract interaction with the prestashop api.
 *
 */
class WebserviceGuesser extends AbstractGuesser
{
    /** @var Webservice */
    protected $webservice;

    /** @var PrestashopSoapClientFactory */
    protected $prestashopSoapClientFactory;

    /**
     * @param PrestashopSoapClientFactory $prestashopSoapClientFactory
     */
    public function __construct(PrestashopSoapClientFactory $prestashopSoapClientFactory)
    {
        $this->prestashopSoapClientFactory = $prestashopSoapClientFactory;
    }

    /**
     * Get the Webservice corresponding to the given Prestashop parameters.
     *
     * @param PrestashopRestClientParameters $clientParameters
     *
     * @throws NotSupportedVersionException If the prestashop version is not supported
     *
     * @return Webservice
     */
    public function getWebservice(PrestashopRestClientParameters $clientParameters)
    {
        if (!$this->webservice) {
            $client         = $this->prestashopSoapClientFactory->getPrestashopSoapClient($clientParameters);
            $prestashopVersion = $this->getPrestashopVersion($client);

            switch ($prestashopVersion) {
                case AbstractGuesser::UNKNOWN_VERSION:
                case AbstractGuesser::PRESTASHOP_VERSION_1_14:
                case AbstractGuesser::PRESTASHOP_VERSION_1_13:
                case AbstractGuesser::PRESTASHOP_VERSION_1_12:
                case AbstractGuesser::PRESTASHOP_VERSION_1_11:
                    $this->webservice = new PrestashopWebservice($client);
                    break;
                default:
                    throw new NotSupportedVersionException(AbstractGuesser::PRESTASHOP_VERSION_NOT_SUPPORTED_MESSAGE);
            }
        }

        return $this->webservice;
    }
}
