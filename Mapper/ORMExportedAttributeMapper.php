<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Mapper;

use Pim\Bundle\PrestashopConnectorBundle\Manager\AttributeMappingManager;
use Pim\Bundle\PrestashopConnectorBundle\Merger\PrestashopMappingMerger;
use Pim\Bundle\PrestashopConnectorBundle\Validator\Constraints\HasValidCredentialsValidator;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopSoapClientParameters;

/**
 * Prestashop exported attribute mapper.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ORMExportedAttributeMapper extends Mapper
{
    /** @var PrestashopSoapClientParameters */
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
            ->getAllPrestashopAttribute($this->clientParameters->getSoapUrl());

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
     * @param PrestashopSoapClientParameters $clientParameters
     * @param string                      $defaultStoreView
     */
    public function setParameters(PrestashopSoapClientParameters $clientParameters, $defaultStoreView)
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
            return sha1(sprintf(Mapper::IDENTIFIER_FORMAT, $rootIdentifier, $this->clientParameters->getSoapUrl()));
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