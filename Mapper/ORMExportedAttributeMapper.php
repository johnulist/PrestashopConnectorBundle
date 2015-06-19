<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Mapper;

use Pim\Bundle\PrestashopConnectorBundle\Manager\AttributeMappingManager;
use Pim\Bundle\PrestashopConnectorBundle\Merger\PrestashopMappingMerger;
use Pim\Bundle\PrestashopConnectorBundle\Validator\Constraints\HasValidCredentialsValidator;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopRestClientParameters;

/**
 * Prestashop exported attribute mapper.
 *
 */
class ORMExportedAttributeMapper extends Mapper
{
    /** @var PrestashopRestClientParameters */
    protected $clientParameters;

    /** @var HasValidCredentialsValidator */
    protected $hasValidCredentialsValidator;

    /** @var AttributeMappingManager */
    protected $attributeMappingManager;

    /** @var string */
    protected $rootIdentifier;

    /** @var string */
    protected $defaultStoreView;

    /** @var PrestashopMappingMerger */
    protected $attributeCodeMappingMerger;

    /**
     * @param HasValidCredentialsValidator $hasValidCredentialsValidator
     * @param string                       $rootIdentifier
     * @param AttributeMappingManager      $attributeMappingManager
     * @param PrestashopMappingMerger         $attributeCodeMappingMerger
     */
    public function __construct(
        HasValidCredentialsValidator $hasValidCredentialsValidator,
        AttributeMappingManager $attributeMappingManager,
        PrestashopMappingMerger $attributeCodeMappingMerger,
        $rootIdentifier
    ) {
        $this->hasValidCredentialsValidator = $hasValidCredentialsValidator;
        $this->rootIdentifier               = $rootIdentifier;
        $this->attributeMappingManager      = $attributeMappingManager;
        $this->attributeCodeMappingMerger   = $attributeCodeMappingMerger;
    }

    /**
     * {@inheritdoc}
     */
    public function getMapping()
    {
        $prestashopAttributeMappings = $this->attributeMappingManager
            ->getAllPrestashopAttribute($this->clientParameters->getPrestashopUrl());

        $attributeCodeMapping = $this->attributeCodeMappingMerger->getMapping();

        $mappingCollection = new MappingCollection();

        foreach ($prestashopAttributeMappings as $prestashopAttributeMapping) {
            $pimAttributeCode = $prestashopAttributeMapping->getAttribute()->getCode();
            $mappingCollection->add(
                [
                    'source'    => $attributeCodeMapping->getTarget($pimAttributeCode),
                    'target'    => $prestashopAttributeMapping->getPrestashopAttributeId(),
                    'deletable' => true,
                ]
            );
        }

        return $mappingCollection;
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

        $this->attributeCodeMappingMerger->setParameters($clientParameters, $defaultStoreView);
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier($rootIdentifier = 'attribute_id')
    {
        if ($this->isValid()) {
            return sha1(sprintf(Mapper::IDENTIFIER_FORMAT, $rootIdentifier, $this->clientParameters->getPrestashopUrl()));
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
