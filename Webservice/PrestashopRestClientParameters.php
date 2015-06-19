<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Webservice;

/**
 * Prestashop rest client parameters.
 *
 */
class PrestashopRestClientParameters
{

    /** @var string */
    protected $restApiKey;

    /** @var string Prestashop Url (only the domain) */
    protected $prestashopUrl;

    /** @var string */
    protected $defaultStoreView;

    /** @var string */
    protected $httpLogin;

    /** @var string */
    protected $httpPassword;

    /** @var boolean Are parameters valid or not ? */
    protected $isValid;

    /**
     * @param string $restApiKey       Prestashop soap api key
     * @param string $prestashopUrl       Prestashop url (only the domain)
     * @param string $defaultStoreView Default store view
     * @param string $httpLogin        Login http authentication
     * @param string $httpPassword     Password http authentication
     */
    public function __construct(
        $restApiKey,
        $prestashopUrl,
        $defaultStoreView,
        $httpLogin = null,
        $httpPassword = null
    ) {
        $this->restApiKey       = $restApiKey;
        $this->prestashopUrl       = $prestashopUrl;
        $this->defaultStoreView = $defaultStoreView;
        $this->httpLogin        = $httpLogin;
        $this->httpPassword     = $httpPassword;
    }

    /**
     * Get hash to uniquely identify parameters even in different instances.
     *
     * @return string
     */
    public function getHash()
    {
        return md5(
            $this->restApiKey.
            $this->prestashopUrl.
            $this->defaultStoreView.
            $this->httpLogin.
            $this->httpPassword
        );
    }

    /**
     * Are parameters valid or not ?
     *
     * @return boolean
     */
    public function isValid()
    {
        return $this->isValid;
    }

    /**
     * @param boolean $state
     */
    public function setValidation($state)
    {
        $this->isValid = $state;
    }

    /**
     * @return string
     */
    public function getRestApiKey()
    {
        return $this->restApiKey;
    }

    /**
     * Prestashop url is the domain name
     *
     * @return string
     */
    public function getPrestashopUrl()
    {
        return $this->prestashopUrl;
    }

    /**
     * @return string
     */
    public function getDefaultstoreView()
    {
        return $this->defaultStoreView;
    }

    /**
     * @return string
     */
    public function getHttpLogin()
    {
        return $this->httpLogin;
    }

    /**
     * @return string
     */
    public function getHttpPassword()
    {
        return $this->httpPassword;
    }
}
