<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Cleaner;

use Pim\Bundle\PrestashopConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\RestCallException;
use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Pim\Bundle\CatalogBundle\Model\AbstractAttribute;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopRestClientParametersRegistry;
use Doctrine\ORM\EntityManager;

/**
 * Prestashop option cleaner.
 *
 */
class OptionCleaner extends Cleaner
{
    /** @var EntityManager */
    protected $em;

    /** @var string */
    protected $attributeClassName;

    /** @var string */
    protected $optionClassName;

    /**
     * @param WebserviceGuesser                   $webserviceGuesser
     * @param EntityManager                       $em
     * @param string                              $attributeClassName
     * @param string                              $optionClassName
     * @param PrestashopRestClientParametersRegistry $clientParametersRegistry
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        EntityManager $em,
        $attributeClassName,
        $optionClassName,
        PrestashopRestClientParametersRegistry $clientParametersRegistry
    ) {
        parent::__construct($webserviceGuesser, $clientParametersRegistry);

        $this->em                 = $em;
        $this->attributeClassName = $attributeClassName;
        $this->optionClassName    = $optionClassName;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        parent::beforeExecute();

        $prestashopOptions = $this->webservice->getAllAttributesOptions();

        foreach ($prestashopOptions as $attributeCode => $options) {
            $attribute = $this->getAttribute($attributeCode);

            $this->cleanOptions($options, $attribute);
        }
    }

    /**
     * Clean options.
     *
     * @param array             $options
     * @param AbstractAttribute $attribute
     *
     * @throws InvalidItemException If clean doesn't goes well
     */
    protected function cleanOptions(array $options, AbstractAttribute $attribute = null)
    {
        foreach ($options as $optionLabel => $optionValue) {
            if ($attribute !== null &&
                !in_array($attribute->getCode(), $this->getIgnoredAttributes()) &&
                $this->getOption($optionLabel, $attribute) === null
            ) {
                try {
                    $this->handleOptionNotInPimAnymore($optionValue, $attribute->getCode());
                } catch (RestCallException $e) {
                    throw new InvalidItemException($e->getMessage(), [$optionLabel]);
                }
            }
        }
    }

    /**
     * Handle deletion or disabling of options which are not in PIM anymore.
     *
     * @param string $optionId
     * @param string $attributeCode
     *
     * @throws InvalidItemException
     */
    protected function handleOptionNotInPimAnymore($optionId, $attributeCode)
    {
        if ($this->notInPimAnymoreAction === self::DELETE) {
            try {
                $this->webservice->deleteOption($optionId, $attributeCode);
                $this->stepExecution->incrementSummaryInfo('option_deleted');
            } catch (RestCallException $e) {
                throw new InvalidItemException($e->getMessage(), [$optionId]);
            }
        }
    }

    /**
     * Get attribute for attribute code.
     *
     * @param string $attributeCode
     *
     * @return AbstractAttribute
     */
    protected function getAttribute($attributeCode)
    {
        return $this->em->getRepository($this->attributeClassName)->findOneBy(['code' => $attributeCode]);
    }

    /**
     * Get option for option label and attribute.
     *
     * @param string            $optionLabel
     * @param AbstractAttribute $attribute
     *
     * @return \Pim\Bundle\CatalogBundle\Entity\AttributeOption
     */
    protected function getOption($optionLabel, AbstractAttribute $attribute)
    {
        return $this->em->getRepository($this->optionClassName)->findOneBy(
            ['code' => $optionLabel, 'attribute' => $attribute]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        $configurationFields = parent::getConfigurationFields();

        $configurationFields['notInPimAnymoreAction']['options']['choices'] = [
            Cleaner::DO_NOTHING => 'pim_prestashop_connector.export.do_nothing.label',
            Cleaner::DELETE     => 'pim_prestashop_connector.export.delete.label',
        ];

        $configurationFields['notInPimAnymoreAction']['options']['help'] =
            'pim_prestashop_connector.export.notInPimAnymoreAction.help';
        $configurationFields['notInPimAnymoreAction']['options']['label'] =
            'pim_prestashop_connector.export.notInPimAnymoreAction.label';

        return $configurationFields;
    }

    /**
     * @return string[]
     */
    protected function getIgnoredAttributes()
    {
        return [
            'visibility',
            'tax_class_id',
        ];
    }
}
