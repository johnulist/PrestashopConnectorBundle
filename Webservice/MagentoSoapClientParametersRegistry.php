<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Webservice;

/**
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class PrestashopSoapClientParametersRegistry
{
    /**
     * Array of all PrestashopSoapClientParameters instances.
     *
     * @var array
     */
    protected $instances;

    /**
     * Gives PrestashopSoapClientParameters which corresponding to given parameters.
     *
     * @param array $soapParameters Associative array which contains soap parameters
     *
     * @return PrestashopSoapClientParameters
     */
    public function getInstance(
        $soapUsername,
        $soapApiKey,
        $prestashopUrl,
        $wsdlUrl,
        $defaultStoreView = Webservice::SOAP_DEFAULT_STORE_VIEW,
        $httpLogin = null,
        $httpPassword = null
    ) {
        $hash = md5(
            $soapUsername.
            $soapApiKey.
            $prestashopUrl.
            $wsdlUrl.
            $defaultStoreView.
            $httpLogin.
            $httpPassword
        );

        if (!isset($this->instances[$hash])) {
            $this->instances[$hash] = new PrestashopSoapClientParameters(
                $soapUsername,
                $soapApiKey,
                $prestashopUrl,
                $wsdlUrl,
                $defaultStoreView,
                $httpLogin,
                $httpPassword
            );
        }

        return $this->instances[$hash];
    }
}
