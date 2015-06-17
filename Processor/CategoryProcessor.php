<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Processor;

use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Pim\Bundle\PrestashopConnectorBundle\Guesser\NormalizerGuesser;
use Pim\Bundle\PrestashopConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\PrestashopConnectorBundle\Manager\LocaleManager;
use Pim\Bundle\PrestashopConnectorBundle\Merger\PrestashopMappingMerger;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\AbstractNormalizer;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\CategoryNormalizer;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\Exception\NormalizeException;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopSoapClientParametersRegistry;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\Exception\CategoryNotMappedException;

/**
 * Prestashop category processor.
 *
 */
class CategoryProcessor extends AbstractProcessor
{
    /** @var string */
    protected $categoryMapping;

    /** @var PrestashopMappingMerger */
    protected $categoryMappingMerger;

    /** @var CategoryNormalizer */
    protected $categoryNormalizer;

    /** @var boolean */
    protected $isAnchor;

    /** @var boolean */
    protected $urlKey;

    /**
     * @param WebserviceGuesser                   $webserviceGuesser
     * @param NormalizerGuesser                   $normalizerGuesser
     * @param LocaleManager                       $localeManager
     * @param PrestashopMappingMerger                $storeViewMappingMerger
     * @param PrestashopMappingMerger                $categoryMappingMerger
     * @param PrestashopSoapClientParametersRegistry $clientParametersRegistry
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        NormalizerGuesser $normalizerGuesser,
        LocaleManager $localeManager,
        PrestashopMappingMerger $storeViewMappingMerger,
        PrestashopMappingMerger $categoryMappingMerger,
        PrestashopSoapClientParametersRegistry $clientParametersRegistry
    ) {
        parent::__construct(
            $webserviceGuesser,
            $normalizerGuesser,
            $localeManager,
            $storeViewMappingMerger,
            $clientParametersRegistry
        );

        $this->categoryMappingMerger = $categoryMappingMerger;
    }

    /**
     * Get category mapping from merger.
     *
     * @return string JSON
     */
    public function getCategoryMapping()
    {
        return json_encode($this->categoryMappingMerger->getMapping()->toArray());
    }

    /**
     * Set category mapping in parameters AND in database.
     *
     * @param string $categoryMapping JSON
     *
     * @return CategoryProcessor
     */
    public function setCategoryMapping($categoryMapping)
    {
        $decodedCategoryMapping = json_decode($categoryMapping, true);

        if (!is_array($decodedCategoryMapping)) {
            $decodedCategoryMapping = [$decodedCategoryMapping];
        }

        $this->categoryMappingMerger->setParameters($this->getClientParameters(), $this->getDefaultStoreView());
        $this->categoryMappingMerger->setMapping($decodedCategoryMapping);
        $this->categoryMapping = $this->getCategoryMapping();

        return $this;
    }

    /**
     * @return boolean
     */
    public function isIsAnchor()
    {
        return $this->isAnchor;
    }

    /**
     * @param boolean $isAnchor
     *
     * @return CategoryProcessor
     */
    public function setIsAnchor($isAnchor)
    {
        $this->isAnchor = $isAnchor;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isUrlKey()
    {
        return $this->urlKey;
    }

    /**
     * @param boolean $urlKey
     *
     * @return CategoryProcessor
     */
    public function setUrlKey($urlKey)
    {
        $this->urlKey = $urlKey;

        return $this;
    }

    /**
     * Function called before all process.
     */
    protected function beforeExecute()
    {
        parent::beforeExecute();

        $this->categoryNormalizer = $this->normalizerGuesser->getCategoryNormalizer($this->getClientParameters());

        $prestashopStoreViews = $this->webservice->getStoreViewsList();
        $prestashopCategories = $this->webservice->getCategoriesStatus();

        $this->globalContext = array_merge(
            $this->globalContext,
            [
                'prestashopCategories' => $prestashopCategories,
                'prestashopUrl'        => $this->getSoapUrl(),
                'defaultLocale'     => $this->defaultLocale,
                'prestashopStoreViews' => $prestashopStoreViews,
                'categoryMapping'   => $this->categoryMappingMerger->getMapping(),
                'defaultStoreView'  => $this->getDefaultStoreView(),
                'is_anchor'         => $this->isAnchor,
                'urlKey'            => $this->urlKey,
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidItemException
     */
    public function process($categories)
    {
        $this->beforeExecute();

        $normalizedCategories = [
            'create'    => [],
            'update'    => [],
            'move'      => [],
            'variation' => [],
        ];

        $categories = is_array($categories) ? $categories : [$categories];

        foreach ($categories as $category) {
            if ($category->getParent()) {
                try {
                    $normalizedCategory = $this->categoryNormalizer->normalize(
                        $category,
                        AbstractNormalizer::PRESTASHOP_FORMAT,
                        $this->globalContext
                    );

                    $normalizedCategories = array_merge_recursive($normalizedCategories, $normalizedCategory);
                } catch (CategoryNotMappedException $e) {
                    if (null !== $category->getParent() && $category->getParent()->isRoot()) {
                        throw new InvalidItemException(
                            $e->getMessage(),
                            [
                                'category_id'      => $category->getId(),
                                'category_code'    => $category->getCode(),
                                'category_label'   => $category->getLabel(),
                                'root_category_id' => $category->getRoot(),
                            ]
                        );
                    }
                } catch (NormalizeException $e) {
                    throw new InvalidItemException(
                        $e->getMessage(),
                        [
                            'category_id'      => $category->getId(),
                            'category_code'    => $category->getCode(),
                            'category_label'   => $category->getLabel(),
                            'root_category_id' => $category->getRoot(),
                        ]
                    );
                }
            }
        }

        return $normalizedCategories;
    }

    /**
     * Called after the configuration is set.
     */
    protected function afterConfigurationSet()
    {
        parent::afterConfigurationSet();

        $this->categoryMappingMerger->setParameters($this->getClientParameters(), $this->getDefaultStoreView());
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return array_merge(
            parent::getConfigurationFields(),
            [
                'isAnchor' => [
                    'type'    => 'checkbox',
                    'options' => [
                        'help'  => 'pim_prestashop_connector.export.isAnchor.help',
                        'label' => 'pim_prestashop_connector.export.isAnchor.label',
                    ],
                ],
                'urlKey' => [
                    'type'    => 'checkbox',
                    'options' => [
                        'help'  => 'pim_prestashop_connector.export.urlKey.help',
                        'label' => 'pim_prestashop_connector.export.urlKey.label',
                    ],
                ],
            ],
            $this->categoryMappingMerger->getConfigurationField()
        );
    }
}
