<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Mapper;

use Pim\Bundle\PrestashopConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\PrestashopConnectorBundle\Validator\Constraints\HasValidCredentialsValidator;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\RestCallException;

/**
 * Prestashop family mapper.
 *
 */
class PrestashopFamilyMapper extends PrestashopMapper
{
    /** @var WebserviceGuesser */
    protected $webserviceGuesser;

    /**
     * @param HasValidCredentialsValidator $hasValidCredentialsValidator
     * @param WebserviceGuesser            $webserviceGuesser
     */
    public function __construct(
        HasValidCredentialsValidator $hasValidCredentialsValidator,
        WebserviceGuesser $webserviceGuesser
    ) {
        parent::__construct($hasValidCredentialsValidator);

        $this->webserviceGuesser = $webserviceGuesser;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllTargets()
    {
        $targets = [];

        if ($this->isValid()) {
            try {
                $families = $this->webserviceGuesser->getWebservice($this->clientParameters)->getAttributeSetList();
            } catch (RestCallException $e) {
                return array();
            }

            foreach ($families as $familyId => $family) {
                $targets[] = ['id' => $familyId, 'name' => $family['name']];
            }
        }

        return $targets;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier($rootIdentifier = 'family')
    {
        return parent::getIdentifier($rootIdentifier);
    }
}
