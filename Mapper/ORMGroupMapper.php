<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Mapper;

use Pim\Bundle\CatalogBundle\Entity\AttributeGroup;
use Pim\Bundle\PrestashopConnectorBundle\Manager\SimpleMappingManager;
use Pim\Bundle\PrestashopConnectorBundle\Manager\AttributeGroupMappingManager;
use Pim\Bundle\PrestashopConnectorBundle\Validator\Constraints\HasValidCredentialsValidator;

/**
 * Prestashop group mapper.
 *
 */
class ORMGroupMapper extends ORMMapper
{
    /** @var AttributeGroupMappingManager */
    protected $attributeGroupManager;

    /**
     * @param HasValidCredentialsValidator $hasValidCredentialsValidator
     * @param SimpleMappingManager         $simpleMappingManager
     * @param AttributeGroupMappingManager $attributeGroupManager
     * @param string                       $rootIdentifier
     */
    public function __construct(
        HasValidCredentialsValidator $hasValidCredentialsValidator,
        SimpleMappingManager         $simpleMappingManager,
        AttributeGroupMappingManager $attributeGroupManager,
        $rootIdentifier
    ) {
        parent::__construct($hasValidCredentialsValidator, $simpleMappingManager, $rootIdentifier);

        $this->groupManager = $attributeGroupManager;
    }

    /**
     * @param AttributeGroup $group
     *
     * @return array
     */
    public function getAllSources(AttributeGroup $group = null)
    {
        $sources = [];

        if ($this->isValid()) {
            $groups = $this->attributeGroupManager->getAllGroups();

            foreach ($groups as $group) {
                $sources[] = ['id' => $group->getCode(), 'name' => $group->getCode()];
            }
        }

        return $sources;
    }
}
