<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Mapper;

use Pim\Bundle\PrestashopConnectorBundle\Manager\SimpleMappingManager;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopSoapClientParameters;
use Pim\Bundle\PrestashopConnectorBundle\Validator\Constraints\HasValidCredentialsValidator;

/**
 * Mapper for ORM.
 *
 */
class ORMPimMapper extends ORMMapper
{
    /** @var PrestashopSoapClientParameters */
    protected $clientParameters;

    /** @var HasValidCredentialsValidator */
    protected $hasValidCredentialsValidator;

    /**
     * @param HasValidCredentialsValidator $hasValidCredentialsValidator
     * @param SimpleMappingManager         $simpleMappingManager
     * @param string                       $rootIdentifier
     */
    public function __construct(
        HasValidCredentialsValidator $hasValidCredentialsValidator,
        SimpleMappingManager $simpleMappingManager,
        $rootIdentifier
    ) {
        parent::__construct($simpleMappingManager, $rootIdentifier);
        $this->hasValidCredentialsValidator = $hasValidCredentialsValidator;
    }

    /**
     * Set mapper parameters.
     *
     * @param PrestashopSoapClientParameters $clientParameters
     */
    public function setParameters(PrestashopSoapClientParameters $clientParameters)
    {
        $this->clientParameters = $clientParameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier($rootIdentifier = 'generic')
    {
        if ($this->isValid()) {
            return sha1(sprintf(self::IDENTIFIER_FORMAT, $rootIdentifier, $this->clientParameters->getSoapUrl()));
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
