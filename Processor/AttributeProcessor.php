<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Processor;

use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Pim\Bundle\CatalogBundle\Model\AbstractAttribute;
use Pim\Bundle\PrestashopConnectorBundle\Guesser\NormalizerGuesser;
use Pim\Bundle\PrestashopConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\PrestashopConnectorBundle\Manager\GroupManager;
use Pim\Bundle\PrestashopConnectorBundle\Manager\LocaleManager;
use Pim\Bundle\PrestashopConnectorBundle\Merger\PrestashopMappingMerger;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\AbstractNormalizer;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\Exception\NormalizeException;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopSoapClientParametersRegistry;

/**
 * Prestashop attributes processor.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class AttributeProcessor extends AbstractProcessor
{
    /** @var PrestashopMappingMerger */
    protected $attributeMappingMerger;

    /** @var \Pim\Bundle\PrestashopConnectorBundle\Normalizer\AttributeNormalizer */
    protected $attributeNormalizer;

    /** @var GroupManager */
    protected $groupManager;

    /** @var string */
    protected $attributeCodeMapping = '';

    /**
     * @param WebserviceGuesser                   $webserviceGuesser
     * @param NormalizerGuesser                   $normalizerGuesser
     * @param LocaleManager                       $localeManager
     * @param PrestashopMappingMerger                $storeViewMappingMerger
     * @param PrestashopMappingMerger                $attributeMappingMerger
     * @param PrestashopSoapClientParametersRegistry $clientParametersRegistry
     * @param GroupManager                        $groupManager
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        NormalizerGuesser $normalizerGuesser,
        LocaleManager $localeManager,
        PrestashopMappingMerger $storeViewMappingMerger,
        PrestashopMappingMerger $attributeMappingMerger,
        PrestashopSoapClientParametersRegistry $clientParametersRegistry,
        GroupManager $groupManager
    ) {
        parent::__construct(
            $webserviceGuesser,
            $normalizerGuesser,
            $localeManager,
            $storeViewMappingMerger,
            $clientParametersRegistry
        );

        $this->attributeMappingMerger = $attributeMappingMerger;
        $this->groupManager           = $groupManager;
    }

    /**
     * Set attribute code mapping in parameters AND in database.
     *
     * @param string $attributeCodeMapping JSON
     *
     * @return AttributeProcessor
     */
    public function setAttributeCodeMapping($attributeCodeMapping)
    {
        $decodedAttributeCodeMapping = json_decode($attributeCodeMapping, true);

        if (!is_array($decodedAttributeCodeMapping)) {
            $decodedAttributeCodeMapping = [$decodedAttributeCodeMapping];
        }

        $this->attributeMappingMerger->setParameters($this->getClientParameters(), $this->getDefaultStoreView());
        $this->attributeMappingMerger->setMapping($decodedAttributeCodeMapping);
        $this->attributeCodeMapping = $this->getAttributeCodeMapping();

        return $this;
    }

    /**
     * Get attribute code mapping.
     *
     * @return string JSON
     */
    public function getAttributeCodeMapping()
    {
        return json_encode($this->attributeMappingMerger->getMapping()->toArray());
    }

    /**
     * {@inheritdoc}
     */
    protected function beforeExecute()
    {
        parent::beforeExecute();

        $prestashopStoreViews = $this->webservice->getStoreViewsList();

        $this->attributeNormalizer = $this->normalizerGuesser->getAttributeNormalizer($this->getClientParameters());
        $this->globalContext['prestashopAttributes']        = $this->webservice->getAllAttributes();
        $this->globalContext['prestashopAttributesOptions'] = $this->webservice->getAllAttributesOptions();
        $this->globalContext['attributeCodeMapping']     = $this->attributeMappingMerger->getMapping();
        $this->globalContext['prestashopStoreViews']        = $prestashopStoreViews;
        $this->globalContext['axisAttributes']           = $this->getAxisAttributes();
    }

    /**
     * {@inheritdoc}
     */
    public function process($attribute)
    {
        $this->beforeExecute();

        $this->globalContext['create'] = !$this->prestashopAttributeExists(
            $attribute,
            $this->globalContext['prestashopAttributes']
        );

        return [$attribute, $this->normalizeAttribute($attribute, $this->globalContext)];
    }

    /**
     * Test if an attribute exist on prestashop.
     *
     * @param AbstractAttribute $attribute
     * @param array             $prestashopAttributes
     *
     * @return boolean
     */
    protected function prestashopAttributeExists(AbstractAttribute $attribute, array $prestashopAttributes)
    {
        return array_key_exists(
            strtolower($this->attributeMappingMerger->getMapping()->getTarget($attribute->getCode())),
            $prestashopAttributes
        );
    }

    /**
     * Normalize the given attribute.
     *
     * @param AbstractAttribute $attribute
     * @param array             $context
     *
     * @throws InvalidItemException If a problem occurred with the normalizer
     *
     * @return array
     */
    protected function normalizeAttribute(AbstractAttribute $attribute, array $context)
    {
        try {
            $processedItem = $this->attributeNormalizer->normalize(
                $attribute,
                AbstractNormalizer::MAGENTO_FORMAT,
                $context
            );
        } catch (NormalizeException $e) {
            throw new InvalidItemException($e->getMessage(), [$attribute]);
        }

        return $processedItem;
    }

    /**
     * Called after the configuration is set.
     */
    protected function afterConfigurationSet()
    {
        parent::afterConfigurationSet();

        $this->attributeMappingMerger->setParameters($this->getClientParameters(), $this->getDefaultStoreView());
    }

    /**
     * Get attribute axis.
     *
     * @return array
     */
    protected function getAxisAttributes()
    {
        $result = [];

        $attributeAxis = $this->groupManager->getRepository()->getAxisAttributes();

        foreach ($attributeAxis as $attribute) {
            $result[] = $attribute['code'];
        }

        return array_unique($result);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return array_merge(
            parent::getConfigurationFields(),
            $this->attributeMappingMerger->getConfigurationField()
        );
    }
}
