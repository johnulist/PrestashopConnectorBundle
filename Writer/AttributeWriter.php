<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Writer;

use Pim\Bundle\CatalogBundle\Model\AbstractAttribute;
use Pim\Bundle\PrestashopConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\PrestashopConnectorBundle\Manager\AttributeMappingManager;
use Pim\Bundle\PrestashopConnectorBundle\Manager\FamilyMappingManager;
use Pim\Bundle\PrestashopConnectorBundle\Manager\AttributeGroupMappingManager;
use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Pim\Bundle\PrestashopConnectorBundle\Merger\PrestashopMappingMerger;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\RestCallException;
use Pim\Bundle\CatalogBundle\Entity\Family;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopRestClientParametersRegistry;

/**
 * Prestashop attribute writer. Add attributes to groups and attribute sets on prestashop side.
 *
 */
class AttributeWriter extends AbstractWriter
{
    /** @staticvar int */
    const ATTRIBUTE_UPDATE_SIZE               = 2;

    /** @staticvar string */
    const SOAP_FAULT_ATTRIBUTE_ALREADY_IN_SET = '109';

    /** @staticvar string */
    const SOAP_FAULT_GROUP_ALREADY_IN_SET     = '112';

    /** @var AttributeMappingManager */
    protected $attributeMappingManager;

    /** @var AbstractAttribute */
    protected $attribute;

    /** @var FamilyMappingManager */
    protected $familyMappingManager;

    /** @var AttributeGroupMappingManager */
    protected $attributeGroupMappingManager;

    /** @var PrestashopMappingMerger */
    protected $attributeIdMappingMerger;

    /**
     * @param WebserviceGuesser                   $webserviceGuesser
     * @param FamilyMappingManager                $familyMappingManager
     * @param AttributeMappingManager             $attributeMappingManager
     * @param AttributeGroupMappingManager        $attributeGroupMappingManager
     * @param PrestashopMappingMerger                $attributeIdMappingMerger
     * @param PrestashopRestClientParametersRegistry $clientParametersRegistry
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        FamilyMappingManager $familyMappingManager,
        AttributeMappingManager $attributeMappingManager,
        AttributeGroupMappingManager $attributeGroupMappingManager,
        PrestashopMappingMerger $attributeIdMappingMerger,
        PrestashopRestClientParametersRegistry $clientParametersRegistry
    ) {
        parent::__construct($webserviceGuesser, $clientParametersRegistry);

        $this->attributeMappingManager      = $attributeMappingManager;
        $this->familyMappingManager         = $familyMappingManager;
        $this->attributeGroupMappingManager = $attributeGroupMappingManager;
        $this->attributeIdMappingMerger     = $attributeIdMappingMerger;

        $this->attributeIdMappingMerger->setParameters($this->getClientParameters(), $this->getDefaultStoreView());
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $attributes)
    {
        $this->beforeExecute();

        foreach ($attributes as $attribute) {
            try {
                $pimAttribute = $attribute[0];
                $this->addGroupToAttributeSet($pimAttribute);
                $this->handleAttribute($attribute[1], $pimAttribute);
            } catch (RestCallException $e) {
                throw new InvalidItemException($e->getMessage(), [$pimAttribute]);
            }
        }
    }

    /**
     * Handle attribute creation and update.
     *
     * @param array             $attribute
     * @param AbstractAttribute $pimAttribute
     *
     * @throws InvalidItemException
     */
    protected function handleAttribute(array $attribute, $pimAttribute)
    {
        if (count($attribute) === self::ATTRIBUTE_UPDATE_SIZE) {
            $this->webservice->updateAttribute($attribute);
            $prestashopAttributeId = $this->attributeIdMappingMerger->getMapping()->getTarget($pimAttribute->getCode());
            $this->manageAttributeSet($prestashopAttributeId, $pimAttribute);

            $this->stepExecution->incrementSummaryInfo('attribute_updated');
        } else {
            $prestashopAttributeId = $this->webservice->createAttribute($attribute);

            $this->manageAttributeSet($prestashopAttributeId, $pimAttribute);

            $this->stepExecution->incrementSummaryInfo('attribute_created');

            $prestashopUrl = $this->getSoapUrl();
            $this->attributeMappingManager->registerAttributeMapping(
                $pimAttribute,
                $prestashopAttributeId,
                $prestashopUrl
            );
        }
    }

    /**
     * Verify if the prestashop attribute id is null else add the attribute to the attribute set.
     *
     * @param integer           $prestashopAttributeId
     * @param AbstractAttribute $pimAttribute
     */
    protected function manageAttributeSet($prestashopAttributeId, $pimAttribute)
    {
        if ($this->attributeIdMappingMerger->getMapping()->getSource($prestashopAttributeId) != $pimAttribute->getCode()) {
            $this->addAttributeToAttributeSet($prestashopAttributeId, $pimAttribute);
        }
    }

    /**
     * Get the prestashop group id.
     *
     * @param AbstractAttribute $pimAttribute
     * @param Family            $pimFamily
     *
     * @return int|null
     */
    protected function getGroupId(AbstractAttribute $pimAttribute, Family $pimFamily)
    {
        $pimGroup = $pimAttribute->getGroup();

        if ($pimGroup !== null) {
            $prestashopGroupId = $this->attributeGroupMappingManager
                ->getIdFromGroup($pimGroup, $pimFamily, $this->getSoapUrl());
        } else {
            $prestashopGroupId = null;
        }

        return $prestashopGroupId;
    }

    /**
     * Add attribute to corresponding attribute sets.
     *
     * @param integer           $prestashopAttributeId
     * @param AbstractAttribute $pimAttribute
     *
     * @throws RestCallException
     */
    protected function addAttributeToAttributeSet($prestashopAttributeId, AbstractAttribute $pimAttribute)
    {
        $families = $pimAttribute->getFamilies();

        foreach ($families as $family) {
            $prestashopGroupId  = $this->getGroupId($pimAttribute, $family);
            $prestashopFamilyId = $this->familyMappingManager->getIdFromFamily($family, $this->getSoapUrl());
            try {
                if (null !== $prestashopFamilyId) {
                    $this->webservice->addAttributeToAttributeSet(
                        $prestashopAttributeId,
                        $prestashopFamilyId,
                        $prestashopGroupId
                    );
                }
            } catch (RestCallException $e) {
                if (static::SOAP_FAULT_ATTRIBUTE_ALREADY_IN_SET === $e->getPrevious()->faultcode) {
                    echo "DEBUG: Attribute ".$prestashopAttributeId.
                        " already exists in attribute set ".$prestashopFamilyId."\n";
                } else {
                    throw $e;
                }
            }
        }
    }

    /**
     * Create a group in an attribute set.
     *
     * @param AbstractAttribute $pimAttribute
     *
     * @throws RestCallException
     */
    protected function addGroupToAttributeSet(AbstractAttribute $pimAttribute)
    {
        $families = $pimAttribute->getFamilies();
        $group = $pimAttribute->getGroup();

        if (isset($group)) {
            $groupName = $group->getCode();

            foreach ($families as $family) {
                $familyPrestashopId = $this->familyMappingManager->getIdFromFamily($family, $this->getSoapUrl());
                if (null === $familyPrestashopId) {
                    $prestashopAttributeSets = $this->webservice->getAttributeSetList();
                    if (array_key_exists($family->getCode(), $prestashopAttributeSets)) {
                        $familyPrestashopId = $prestashopAttributeSets[$family->getCode()];
                    }
                }
                try {
                    $prestashopGroupId = $this->webservice->addAttributeGroupToAttributeSet($familyPrestashopId, $groupName);
                    $this->attributeGroupMappingManager->registerGroupMapping(
                        $group,
                        $family,
                        $prestashopGroupId,
                        $this->getSoapUrl()
                    );
                } catch (RestCallException $e) {
                    if (static::SOAP_FAULT_GROUP_ALREADY_IN_SET === $e->getPrevious()->faultcode) {
                        echo "DEBUG: Group ".$groupName." already exists in attribute set ".$familyPrestashopId."\n";
                    } else {
                        throw $e;
                    }
                }
            }
        }
    }
}
