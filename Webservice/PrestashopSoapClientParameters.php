<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Webservice;

/**
 * Prestashop soap client parameters.
 *
 */
class PrestashopSoapClientParameters
{
    /** @staticvar string */
    const SOAP_WSDL_URL = '/api/soap/?wsdl';

    /** @var string */
    protected $soapUsername;

    /** @var string */
    protected $soapApiKey;

    /** @var string */
    protected $wsdlUrl;

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
     * @param string $soapUsername     Prestashop soap username
     * @param string $soapApiKey       Prestashop soap api key
     * @param string $prestashopUrl       Prestashop url (only the domain)
     * @param string $wsdlUrl          Only wsdl soap api extension
     * @param string $defaultStoreView Default store view
     * @param string $httpLogin        Login http authentication
     * @param string $httpPassword     Password http authentication
     */
    public function __construct(
        $soapUsername,
        $soapApiKey,
        $prestashopUrl,
        $wsdlUrl,
        $defaultStoreView,
        $httpLogin = null,
        $httpPassword = null
    ) {
        $this->soapUsername     = $soapUsername;
        $this->soapApiKey       = $soapApiKey;
        $this->prestashopUrl       = $prestashopUrl;
        $this->wsdlUrl          = $wsdlUrl;
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
            $this->soapUsername.
            $this->soapApiKey.
            $this->prestashopUrl.
            $this->wsdlUrl.
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
    public function getSoapUsername()
    {
        return $this->soapUsername;
    }

    /**
     * @return string
     */
    public function getSoapApiKey()
    {
        return $this->soapApiKey;
    }

    /**
     * Soap url is concatenation between prestashop url and wsdl url
     *
     * @return string
     */
    public function getSoapUrl()
    {
        return $this->prestashopUrl.$this->wsdlUrl;
    }

    /**
     * @return string
     */
    public function getWsdlUrl()
    {
        return $this->wsdlUrl;
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
