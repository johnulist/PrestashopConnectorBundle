<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Mapper;

use Pim\Bundle\PrestashopConnectorBundle\Manager\SimpleMappingManager;
use Pim\Bundle\PrestashopConnectorBundle\Manager\AttributeManager;
use Pim\Bundle\PrestashopConnectorBundle\Validator\Constraints\HasValidCredentialsValidator;

/**
 * Prestashop attribute mapper.
 *
 */
class ORMAttributeCodeMapper extends ORMPimMapper
{
    /** @var AttributeManager */
    protected $attributeManager;

    /**
     * @param HasValidCredentialsValidator $hasValidCredentialsValidator
     * @param SimpleMappingManager         $simpleMappingManager
     * @param string                       $rootIdentifier
     * @param AttributeManager             $attributeManager
     */
    public function __construct(
        HasValidCredentialsValidator $hasValidCredentialsValidator,
        SimpleMappingManager $simpleMappingManager,
        $rootIdentifier,
        AttributeManager $attributeManager
    ) {
        parent::__construct($hasValidCredentialsValidator, $simpleMappingManager, $rootIdentifier);

        $this->attributeManager = $attributeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllSources()
    {
        $targets = [];

        if ($this->isValid()) {
            $attributes = $this->attributeManager->getAttributes();

            foreach ($attributes as $attribute) {
                $targets[] = ['id' => $attribute->getCode(), 'text' => $attribute->getCode()];
            }
        }

        return $targets;
    }
}
