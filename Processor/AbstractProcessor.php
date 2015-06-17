<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Processor;

use Symfony\Component\Validator\Constraints as Assert;
use Akeneo\Bundle\BatchBundle\Item\ItemProcessorInterface;
use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Pim\Bundle\PrestashopConnectorBundle\Item\PrestashopItemStep;
use Pim\Bundle\PrestashopConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\PrestashopConnectorBundle\Guesser\NormalizerGuesser;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\AttributeSetNotFoundException;
use Pim\Bundle\PrestashopConnectorBundle\Manager\LocaleManager;
use Pim\Bundle\PrestashopConnectorBundle\Merger\PrestashopMappingMerger;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopSoapClientParametersRegistry;

/**
 * Prestashop product processor.
 *
 */
abstract class AbstractProcessor extends PrestashopItemStep implements ItemProcessorInterface
{
    /** @var NormalizerGuesser */
    protected $normalizerGuesser;

    /** @var LocaleManager */
    protected $localeManager;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     */
    protected $defaultLocale;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     */
    protected $website = 'base';

    /** @var PrestashopMappingMerger */
    protected $storeViewMappingMerger;

    /** @var string */
    protected $storeviewMapping = '';

    /** @var array */
    protected $globalContext = [];

    /**
     * @param WebserviceGuesser                   $webserviceGuesser
     * @param NormalizerGuesser                   $normalizerGuesser
     * @param LocaleManager                       $localeManager
     * @param PrestashopMappingMerger                $storeViewMappingMerger
     * @param PrestashopSoapClientParametersRegistry $clientParametersRegistry
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        NormalizerGuesser $normalizerGuesser,
        LocaleManager $localeManager,
        PrestashopMappingMerger $storeViewMappingMerger,
        PrestashopSoapClientParametersRegistry $clientParametersRegistry
    ) {
        parent::__construct($webserviceGuesser, $clientParametersRegistry);

        $this->normalizerGuesser      = $normalizerGuesser;
        $this->localeManager          = $localeManager;
        $this->storeViewMappingMerger = $storeViewMappingMerger;
    }

    /**
     * @return string defaultLocale
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * @param string $defaultLocale
     *
     * @return AbstractProcessor
     */
    public function setDefaultLocale($defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;

        return $this;
    }

    /**
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param string $website
     *
     * @return AbstractProcessor
     */
    public function setWebsite($website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * Set store view mapping mapping in parameters AND in database
     *
     * @param string $storeViewMapping JSON
     *
     * @return AbstractProcessor
     */
    public function setStoreViewMapping($storeViewMapping)
    {
        $decodedStoreViewMapping = json_decode($storeViewMapping, true);

        if (!is_array($decodedStoreViewMapping)) {
            $decodedStoreViewMapping = [$decodedStoreViewMapping];
        }

        $this->storeViewMappingMerger->setParameters($this->getClientParameters(), $this->getDefaultStoreView());
        $this->storeViewMappingMerger->setMapping($decodedStoreViewMapping);
        $this->storeviewMapping = $this->getStoreViewMapping();

        return $this;
    }

    /**
     * @return string JSON
     */
    public function getStoreViewMapping()
    {
        return json_encode($this->storeViewMappingMerger->getMapping()->toArray());
    }

    /**
     * Get computed mapping.
     *
     * @param string $mapping
     *
     * @return array
     */
    protected function getComputedMapping($mapping)
    {
        $computedMapping = [];

        foreach (explode(chr(10), $mapping) as $line) {
            $computedLine = explode(':', $line);

            if (isset($computedLine[0]) && isset($computedLine[1])) {
                $computedMapping[$computedLine[0]] = $computedLine[1];
            }
        }

        return $computedMapping;
    }

    /**
     * Function called before all process.
     */
    protected function beforeExecute()
    {
        parent::beforeExecute();

        $this->globalContext['defaultLocale']    = $this->defaultLocale;
        $this->globalContext['storeViewMapping'] = $this->storeViewMappingMerger->getMapping();
        $this->globalContext['defaultStoreView'] = $this->getDefaultStoreView();
    }

    /**
     * Get the attribute set id for the given family code.
     *
     * @param string $familyCode
     * @param mixed  $relatedItem
     *
     * @throws InvalidItemException If The attribute set doesn't exist on Prestashop
     *
     * @return integer
     */
    protected function getAttributeSetId($familyCode, $relatedItem)
    {
        try {
            return $this->webservice
                ->getAttributeSetId(
                    $familyCode
                );
        } catch (AttributeSetNotFoundException $e) {
            throw new InvalidItemException($e->getMessage(), [$relatedItem]);
        }
    }

    /**
     * Called after the configuration is set.
     */
    protected function afterConfigurationSet()
    {
        parent::afterConfigurationSet();

        $this->storeViewMappingMerger->setParameters($this->getClientParameters(), $this->getDefaultStoreView());
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return array_merge(
            parent::getConfigurationFields(),
            [
                'defaultLocale' => [
                    'type'    => 'choice',
                    'options' => [
                        'choices'  => $this->localeManager->getLocaleChoices(),
                        'required' => true,
                        'attr' => [
                            'class' => 'select2',
                        ],
                        'help'  => 'pim_prestashop_connector.export.defaultLocale.help',
                        'label' => 'pim_prestashop_connector.export.defaultLocale.label',
                    ],
                ],
                'website' => [
                    'type'    => 'text',
                    'options' => [
                        'required' => true,
                        'help'  => 'pim_prestashop_connector.export.website.help',
                        'label' => 'pim_prestashop_connector.export.website.label',
                    ],
                ],
            ],
            $this->storeViewMappingMerger->getConfigurationField()
        );
    }
}
