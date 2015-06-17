<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Writer;

use Pim\Bundle\PrestashopConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\PrestashopConnectorBundle\Manager\AttributeMappingManager;
use Pim\Bundle\PrestashopConnectorBundle\Manager\FamilyMappingManager;
use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\SoapCallException;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopSoapClientParametersRegistry;

/**
 * Prestashop attribute set writer.
 *
 * @author    Olivier Soulet <olivier.soulet@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class AttributeSetWriter extends AbstractWriter
{
    /** @var FamilyMappingManager */
    protected $familyMappingManager;

    /** @var AttributeMappingManager */
    protected $attributeMappingManager;

    /**
     * @param WebserviceGuesser                   $webserviceGuesser
     * @param FamilyMappingManager                $familyMappingManager
     * @param AttributeMappingManager             $attributeMappingManager
     * @param PrestashopSoapClientParametersRegistry $clientParametersRegistry
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        FamilyMappingManager $familyMappingManager,
        AttributeMappingManager $attributeMappingManager,
        PrestashopSoapClientParametersRegistry $clientParametersRegistry
    ) {
        parent::__construct($webserviceGuesser, $clientParametersRegistry);

        $this->attributeMappingManager = $attributeMappingManager;
        $this->familyMappingManager    = $familyMappingManager;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        $this->beforeExecute();
        foreach ($items as $item) {
            try {
                $this->handleNewFamily($item);
            } catch (SoapCallException $e) {
                $this->stepExecution->incrementSummaryInfo('family_exists');
            }
        }
    }

    /**
     * Handle family creation.
     *
     * @param array $item
     *
     * @throws InvalidItemException
     */
    protected function handleNewFamily(array $item)
    {
        if (isset($item['families_to_create'])) {
            $pimFamily       = $item['family_object'];
            $prestashopFamilyId = $this->webservice->createAttributeSet($item['families_to_create']['attributeSetName']);
            $prestashopUrl      = $this->getSoapUrl();
            $this->familyMappingManager->registerFamilyMapping(
                $pimFamily,
                $prestashopFamilyId,
                $prestashopUrl
            );
            $this->stepExecution->incrementSummaryInfo('family_created');
        }
    }
}
