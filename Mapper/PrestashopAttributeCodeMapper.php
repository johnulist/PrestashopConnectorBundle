<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Mapper;

use Pim\Bundle\PrestashopConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\PrestashopConnectorBundle\Validator\Constraints\HasValidCredentialsValidator;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\RestCallException;

/**
 * Prestashop attribute code mapper.
 *
 */
class PrestashopAttributeCodeMapper extends PrestashopMapper
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
        if (!$this->isValid()) {
            return new MappingCollection();
        } else {
            try {
                $attributes = $this->webserviceGuesser->getWebservice($this->clientParameters)->getAllAttributes();
            } catch (RestCallException $e) {
                return new MappingCollection();
            }

            $mapping = new MappingCollection();
            foreach (array_keys($attributes) as $attributeCode) {
                if (in_array($attributeCode, $this->mandatoryAttributes())) {
                    $mapping->add(
                        [
                            'source'    => $attributeCode,
                            'target'    => $attributeCode,
                            'deletable' => false,
                        ]
                    );
                }
            }

            return $mapping;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAllTargets()
    {
        $sources = [];

        if ($this->isValid()) {
            $attributeCodes = array_keys(
                $this->webserviceGuesser->getWebservice($this->clientParameters)->getAllAttributes()
            );

            foreach ($attributeCodes as $attributeCode) {
                $sources[] = ['id' => $attributeCode, 'text' => $attributeCode];
            }
        }

        return $sources;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier($rootIdentifier = 'attribute')
    {
        return parent::getIdentifier($rootIdentifier);
    }

    /**
     * @return string[]
     */
    protected function mandatoryAttributes()
    {
        return [
            'name',
            'price',
            'description',
            'short_description',
            'tax_class_id',
            'weight',
        ];
    }
}
