<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Webservice;

class PrestashopRestClientParametersRegistry
{
    /**
     * Array of all PrestashopRestClientParameters instances.
     *
     * @var array
     */
    protected $instances;

    /**
     * Gives PrestashopRestClientParameters which corresponding to given parameters.
     *
     * @param array $restParameters Associative array which contains rest parameters
     *
     * @return PrestashopRestClientParameters
     */
    public function getInstance(
        $restApiKey,
        $prestashopUrl,
        $defaultStoreView = Webservice::REST_DEFAULT_STORE_VIEW,
        $httpLogin = null,
        $httpPassword = null
    ) {
        $hash = md5(
            $restApiKey.
            $prestashopUrl.
            $defaultStoreView.
            $httpLogin.
            $httpPassword
        );

        if (!isset($this->instances[$hash])) {
            $this->instances[$hash] = new PrestashopRestClientParameters(
                $restApiKey,
                $prestashopUrl,
                $defaultStoreView,
                $httpLogin,
                $httpPassword
            );
        }

        return $this->instances[$hash];
    }
}
