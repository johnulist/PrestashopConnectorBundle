<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Mapper;

use Pim\Bundle\CatalogBundle\Entity\Family;
use Pim\Bundle\PrestashopConnectorBundle\Manager\SimpleMappingManager;
use Pim\Bundle\PrestashopConnectorBundle\Manager\FamilyMappingManager;
use Pim\Bundle\PrestashopConnectorBundle\Validator\Constraints\HasValidCredentialsValidator;

/**
 * Prestashop family mapper.
 *
 */
class ORMFamilyMapper extends ORMPimMapper
{
    /** @var FamilyMappingManager */
    protected $familyManager;

    /**
     * @param HasValidCredentialsValidator $hasValidCredentialsValidator
     * @param SimpleMappingManager         $simpleMappingManager
     * @param FamilyMappingManager         $familyManager
     * @param string                       $rootIdentifier
     */
    public function __construct(
        HasValidCredentialsValidator $hasValidCredentialsValidator,
        SimpleMappingManager         $simpleMappingManager,
        FamilyMappingManager         $familyManager,
        $rootIdentifier
    ) {
        parent::__construct($hasValidCredentialsValidator, $simpleMappingManager, $rootIdentifier);

        $this->familyManager = $familyManager;
    }

    /**
     * @param Family $family
     *
     * @return array
     */
    public function getAllSources(Family $family = null)
    {
        $sources = [];

        if ($this->isValid()) {
            $families = $this->familyManager->getFamilies();

            foreach ($families as $family) {
                $sources[] = ['id' => $family->getCode(), 'name' => $family->getCode()];
            }
        }

        return $sources;
    }
}
