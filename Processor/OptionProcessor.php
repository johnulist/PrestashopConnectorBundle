<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Processor;

use Pim\Bundle\CatalogBundle\Entity\AttributeOption;
use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Pim\Bundle\PrestashopConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\SoapCallException;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\AbstractNormalizer;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\Exception\NormalizeException;
use Pim\Bundle\PrestashopConnectorBundle\Guesser\NormalizerGuesser;
use Pim\Bundle\PrestashopConnectorBundle\Manager\LocaleManager;
use Pim\Bundle\PrestashopConnectorBundle\Merger\PrestashopMappingMerger;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopSoapClientParametersRegistry;

/**
 * Prestashop option processor.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class OptionProcessor extends AbstractProcessor
{
    /** @var \Pim\Bundle\PrestashopConnectorBundle\Normalizer\OptionNormalizer */
    protected $optionNormalizer;

    /** @var PrestashopMappingMerger */
    protected $attributeMappingMerger;

    /** @var string */
    protected $attributeCodeMapping;

    /**
     * @param WebserviceGuesser                   $webserviceGuesser
     * @param NormalizerGuesser                   $normalizerGuesser
     * @param LocaleManager                       $localeManager
     * @param PrestashopMappingMerger                $storeViewMappingMerger
     * @param PrestashopMappingMerger                $attributeMappingMerger
     * @param PrestashopSoapClientParametersRegistry $clientParametersRegistry
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        NormalizerGuesser $normalizerGuesser,
        LocaleManager $localeManager,
        PrestashopMappingMerger $storeViewMappingMerger,
        PrestashopMappingMerger $attributeCodeMappingMerger,
        PrestashopSoapClientParametersRegistry $clientParametersRegistry
    ) {
        parent::__construct(
            $webserviceGuesser,
            $normalizerGuesser,
            $localeManager,
            $storeViewMappingMerger,
            $clientParametersRegistry
        );

        $this->attributeCodeMappingMerger = $attributeCodeMappingMerger;
    }

    /**
     * Set attribute code mapping in parameters AND in database.
     *
     * @param string $attributeCodeMapping JSON
     *
     * @return OptionProcessor
     */
    public function setAttributeCodeMapping($attributeCodeMapping)
    {
        $decodedAttributeCodeMapping = json_decode($attributeCodeMapping, true);

        if (!is_array($decodedAttributeCodeMapping)) {
            $decodedAttributeCodeMapping = [$decodedAttributeCodeMapping];
        }

        $this->attributeCodeMappingMerger->setParameters($this->getClientParameters(), $this->getDefaultStoreView());
        $this->attributeCodeMappingMerger->setMapping($decodedAttributeCodeMapping);
        $this->attributeCodeMapping = $this->getAttributeCodeMapping();

        return $this;
    }

    /**
     * Get attribute code mapping from merger.
     *
     * @return string JSON
     */
    public function getAttributeCodeMapping()
    {
        return json_encode($this->attributeCodeMappingMerger->getMapping()->toArray());
    }

    /**
     * {@inheritdoc}
     */
    protected function beforeExecute()
    {
        parent::beforeExecute();

        $this->optionNormalizer = $this->normalizerGuesser->getOptionNormalizer($this->getClientParameters());

        $prestashopStoreViews = $this->webservice->getStoreViewsList();

        $this->globalContext['prestashopStoreViews']    = $prestashopStoreViews;
        $this->globalContext['attributeCodeMapping'] = $this->attributeCodeMappingMerger->getMapping();
    }

    /**
     * {@inheritdoc}
     */
    public function process($groupedOptions)
    {
        $this->beforeExecute();

        $attribute     = $groupedOptions[0]->getAttribute();
        $attributeCode = strtolower($this->globalContext['attributeCodeMapping']->getTarget($attribute->getCode()));

        try {
            $optionsStatus = $this->webservice->getAttributeOptions($attributeCode);
        } catch (SoapCallException $e) {
            throw new InvalidItemException(
                sprintf(
                    'An error occurred during the retrieval of option list of the attribute "%s". This may be '.
                    'due to the fact that "%s" attribute doesn\'t exist on Prestashop side. Please be sure that '.
                    'this attribute is created (mannualy or by export) on Prestashop before options\' export. '.
                    '(Original error : "%s")',
                    $attributeCode,
                    $attributeCode,
                    $e->getMessage()
                ),
                [
                    'code'  => $attribute->getCode(),
                    'label' => $attribute->getLabel(),
                    'type'  => $attribute->getAttributeType(),
                ]
            );
        }

        $this->globalContext['attributeCode'] = $attributeCode;

        $normalizedOptions = [];

        foreach ($groupedOptions as $option) {
            if (!array_key_exists($option->getCode(), $optionsStatus)) {
                $normalizedOptions[] = $this->getNormalizedOption($option, $this->globalContext);
            }
        }

        return $normalizedOptions;
    }

    /**
     * Get the normalized option.
     *
     * @param AttributeOption $option
     * @param array           $context
     *
     * @return array
     *
     * @throws InvalidItemException
     */
    protected function getNormalizedOption(AttributeOption $option, array $context)
    {
        try {
            $normalizedOption = $this->optionNormalizer->normalize(
                $option,
                AbstractNormalizer::MAGENTO_FORMAT,
                $context
            );
        } catch (NormalizeException $e) {
            throw new InvalidItemException($e->getMessage(), [$option]);
        }

        return $normalizedOption;
    }

    /**
     * Called after the configuration is set.
     */
    protected function afterConfigurationSet()
    {
        parent::afterConfigurationSet();

        $this->attributeCodeMappingMerger->setParameters($this->getClientParameters(), $this->getDefaultStoreView());
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return array_merge(
            parent::getConfigurationFields(),
            $this->attributeCodeMappingMerger->getConfigurationField()
        );
    }
}
