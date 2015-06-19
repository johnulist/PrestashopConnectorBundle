<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Mapper;

use Pim\Bundle\PrestashopConnectorBundle\Validator\Constraints\HasValidCredentialsValidator;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopRestClientParameters;

/**
 * Prestashop mapper.
 *
 */
class PrestashopMapper extends Mapper
{
    /** @var PrestashopRestClientParameters */
    protected $clientParameters = null;

    /** @var HasValidCredentialsValidator */
    protected $hasValidCredentialsValidator;

    /* @var string */
    protected $defaultStoreView;

    /**
     * @param HasValidCredentialsValidator $hasValidCredentialsValidator
     */
    public function __construct(HasValidCredentialsValidator $hasValidCredentialsValidator)
    {
        $this->hasValidCredentialsValidator = $hasValidCredentialsValidator;
    }

    /**
     * Set mapper parameters.
     *
     * @param PrestashopRestClientParameters $clientParameters
     * @param string                      $defaultStoreView
     */
    public function setParameters(PrestashopRestClientParameters $clientParameters, $defaultStoreView)
    {
        $this->clientParameters = $clientParameters;
        $this->defaultStoreView = $defaultStoreView;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier($rootIdentifier = 'generic')
    {
        if ($this->isValid()) {
            return sha1(sprintf(self::IDENTIFIER_FORMAT, $rootIdentifier, $this->clientParameters->getPrestashopUrl()));
        } else {
            return '';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isValid()
    {
        return $this->clientParameters !== null &&
            $this->hasValidCredentialsValidator->areValidSoapCredentials($this->clientParameters);
    }
}
