<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Cleaner;

use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopRestClientParametersRegistry;
use Pim\Bundle\PrestashopConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\PrestashopConnectorBundle\Manager\FamilyMappingManager;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\SoapCallException;
use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;

/**
 * Prestashop family cleaner.
 *
 */
class FamilyCleaner extends Cleaner
{
    /** @var FamilyMappingManager */
    protected $familyMappingManager;

    /** @var boolean */
    protected $forceAttributeSetRemoval;

    /**
     * @param WebserviceGuesser                   $webserviceGuesser
     * @param FamilyMappingManager                $familyMappingManager
     * @param PrestashopRestClientParametersRegistry $clientParametersRegistry
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        FamilyMappingManager $familyMappingManager,
        PrestashopRestClientParametersRegistry $clientParametersRegistry
    ) {
        parent::__construct($webserviceGuesser, $clientParametersRegistry);

        $this->familyMappingManager = $familyMappingManager;
    }

    /**
     * @return boolean
     */
    public function isForceAttributeSetRemoval()
    {
        return $this->forceAttributeSetRemoval;
    }

    /**
     * @param boolean $forceAttributeSetRemoval
     *
     * @return FamilyCleaner
     */
    public function setForceAttributeSetRemoval($forceAttributeSetRemoval)
    {
        $this->forceAttributeSetRemoval = $forceAttributeSetRemoval;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        parent::beforeExecute();

        $prestashopFamilies = $this->webservice->getAttributeSetList();

        foreach ($prestashopFamilies as $name => $id) {
            try {
                $this->handleFamilyNotInPimAnymore($name, $id);
            } catch (SoapCallException $e) {
                throw new InvalidItemException($e->getMessage(), [$name]);
            }
        }
    }

    /**
     * Handle deletion of families that are not in PIM anymore.
     *
     * @param string $name
     * @param int    $id
     *
     * @throws InvalidItemException
     */
    protected function handleFamilyNotInPimAnymore($name, $id)
    {
        if (
            $this->notInPimAnymoreAction === self::DELETE &&
            !$this->familyMappingManager->prestashopFamilyExists($id, $this->getSoapUrl()) &&
            !in_array($name, $this->getIgnoredFamilies())
        ) {
            try {
                $this->webservice->removeAttributeSet(
                    $id,
                    $this->forceAttributeSetRemoval
                );
                $this->stepExecution->incrementSummaryInfo('family_deleted');
            } catch (SoapCallException $e) {
                throw new InvalidItemException(
                    $e->getMessage(),
                    [$id],
                    [$e]
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return array_merge(
            parent::getConfigurationFields(),
            [
                'notInPimAnymoreAction' => [
                    'type'    => 'choice',
                    'options' => [
                        'choices'  => [
                            Cleaner::DO_NOTHING => 'pim_prestashop_connector.export.do_nothing.label',
                            Cleaner::DELETE     => 'pim_prestashop_connector.export.delete.label',
                        ],
                        'required' => true,
                        'help'     => 'pim_prestashop_connector.export.notInPimAnymoreAction.help',
                        'label'    => 'pim_prestashop_connector.export.notInPimAnymoreAction.label',
                        'attr'     => ['class' => 'select2'],
                    ],
                ],
                'forceAttributeSetRemoval' => [
                    'type' => 'checkbox',
                    'options' => [
                        'help' => 'pim_prestashop_connector.export.forceAttributeSetRemoval.help',
                        'label' => 'pim_prestashop_connector.export.forceAttributeSetRemoval.label',
                    ],
                ],
            ]
        );
    }

    /**
     * Get all ignored families.
     *
     * @return string[]
     */
    protected function getIgnoredFamilies()
    {
        return [
            'Default',
        ];
    }
}
