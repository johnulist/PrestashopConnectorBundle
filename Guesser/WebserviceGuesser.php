<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Guesser;

use Pim\Bundle\PrestashopConnectorBundle\Webservice\Webservice;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\Webservice16;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\WebserviceEE;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopSoapClientParameters;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopSoapClientFactory;

/**
 * A prestashop soap client to abstract interaction with the prestashop api.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
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
     * @param PrestashopSoapClientParameters $clientParameters
     *
     * @throws NotSupportedVersionException If the prestashop version is not supported
     *
     * @return Webservice
     */
    public function getWebservice(PrestashopSoapClientParameters $clientParameters)
    {
        if (!$this->webservice) {
            $client         = $this->prestashopSoapClientFactory->getPrestashopSoapClient($clientParameters);
            $prestashopVersion = $this->getPrestashopVersion($client);

            switch ($prestashopVersion) {
                case AbstractGuesser::MAGENTO_VERSION_1_14:
                case AbstractGuesser::MAGENTO_VERSION_1_13:
                case AbstractGuesser::MAGENTO_VERSION_1_12:
                case AbstractGuesser::MAGENTO_VERSION_1_11:
                    $this->webservice = new WebserviceEE($client);
                    break;
                case AbstractGuesser::UNKNOWN_VERSION:
                case AbstractGuesser::MAGENTO_VERSION_1_9:
                case AbstractGuesser::MAGENTO_VERSION_1_8:
                case AbstractGuesser::MAGENTO_VERSION_1_7:
                    $this->webservice = new Webservice($client);
                    break;
                case AbstractGuesser::MAGENTO_VERSION_1_6:
                    $this->webservice = new Webservice16($client);
                    break;
                default:
                    throw new NotSupportedVersionException(AbstractGuesser::MAGENTO_VERSION_NOT_SUPPORTED_MESSAGE);
            }
        }

        return $this->webservice;
    }
}
