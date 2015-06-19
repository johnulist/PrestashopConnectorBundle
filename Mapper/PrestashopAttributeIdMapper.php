<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Mapper;

use Pim\Bundle\PrestashopConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\PrestashopConnectorBundle\Validator\Constraints\HasValidCredentialsValidator;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\RestCallException;

/**
 * Prestashop attribute id mapper.
 *
 */
class PrestashopAttributeIdMapper extends PrestashopMapper
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
    public function getMapping()
    {
        $mapping = new MappingCollection();

        if ($this->isValid()) {
            try {
                $attributes = $this->webserviceGuesser->getWebservice($this->clientParameters)->getAllAttributes();
            } catch (RestCallException $e) {
                return $mapping;
            }

            foreach ($attributes as $attribute) {
                $mapping->add(
                    [
                        'source'    => $attribute['code'],
                        'target'    => $attribute['attribute_id'],
                        'deletable' => true,
                    ]
                );
            }
        }

        return $mapping;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllTargets()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAllSources()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier($rootIdentifier = 'attribute_id')
    {
        return parent::getIdentifier($rootIdentifier);
    }
}
