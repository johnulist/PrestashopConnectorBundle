<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Normalizer;

use Pim\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\CatalogBundle\Model\CategoryInterface;
use Pim\Bundle\PrestashopConnectorBundle\Manager\CategoryMappingManager;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\Exception\CategoryNotMappedException;
use Gedmo\Sluggable\Util\Urlizer;

/**
 * A normalizer to transform a category entity into an array.
 *
 */
class CategoryNormalizer extends AbstractNormalizer
{
    /** @var CategoryMappingManager */
    protected $categoryMappingManager;

    /** @var CategoryRepository */
    protected $categoryRepository;

    /**
     * @param ChannelManager         $channelManager
     * @param CategoryMappingManager $categoryMappingManager
     * @param CategoryRepository     $categoryRepository
     */
    public function __construct(
        ChannelManager         $channelManager,
        CategoryMappingManager $categoryMappingManager,
        CategoryRepository     $categoryRepository
    ) {
        parent::__construct($channelManager);

        $this->categoryMappingManager = $categoryMappingManager;
        $this->categoryRepository     = $categoryRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($category, $format = null, array $context = [])
    {
        $normalizedCategory = $this->getDefaultCategory($category, $context);

        //For each storeview, we update the product only with localized attributes
        foreach ($category->getTranslations() as $translation) {
            $storeView = $this->getStoreViewForLocale(
                $translation->getLocale(),
                $context['prestashopStoreViews'],
                $context['storeViewMapping']
            );

            //If a locale for this storeview exist in PIM, we create a translated product in this locale
            if ($storeView) {
                $normalizedCategory['variation'][] = $this->getNormalizedVariationCategory(
                    $category,
                    $translation->getLocale(),
                    $storeView['code'],
                    $context['urlKey']
                );
            }
        }

        return $normalizedCategory;
    }

    /**
     * Get the default category.
     *
     * @param CategoryInterface $category
     * @param array             $context
     *
     * @return array
     */
    protected function getDefaultCategory(CategoryInterface $category, array $context)
    {
        $normalizedCategory = [
            'create'    => [],
            'update'    => [],
            'move'      => [],
            'variation' => [],
        ];

        if ($this->prestashopCategoryExists($category, $context['prestashopCategories'], $context['prestashopUrl'])) {
            $normalizedCategory['update'][] = $this->getNormalizedUpdateCategory(
                $category,
                $context
            );

            if ($this->categoryHasMoved($category, $context)) {
                $normalizedCategory['move'][] = $this->getNormalizedMoveCategory($category, $context);
            }
        } else {
            $normalizedCategory['create'][] = $this->getNormalizedNewCategory(
                $category,
                $context,
                $context['defaultStoreView']
            );
        }

        return $normalizedCategory;
    }

    /**
     * Test if the given category exist on Prestashop side.
     *
     * @param CategoryInterface $category
     * @param array             $prestashopCategories
     * @param string            $prestashopUrl
     *
     * @return boolean
     */
    protected function prestashopCategoryExists(CategoryInterface $category, array $prestashopCategories, $prestashopUrl)
    {
        return ($prestashopCategoryId = $this->getPrestashopCategoryId($category, $prestashopUrl)) !== null &&
            isset($prestashopCategories[$prestashopCategoryId]);
    }

    /**
     * Get category id on Prestashop side for the given category.
     *
     * @param CategoryInterface $category
     * @param string            $prestashopUrl
     *
     * @return int
     */
    protected function getPrestashopCategoryId(CategoryInterface $category, $prestashopUrl)
    {
        return $this->categoryMappingManager->getIdFromCategory($category, $prestashopUrl);
    }

    /**
     * Get new normalized categories.
     *
     * @param CategoryInterface $category
     * @param array             $context
     *
     * @return array
     *
     * @throws CategoryNotMappedException
     */
    protected function getNormalizedNewCategory(CategoryInterface $category, array $context)
    {
        $parentCategoryId = $this->categoryMappingManager->getIdFromCategory(
            $category->getParent(),
            $context['prestashopUrl'],
            $context['categoryMapping']
        );

        $prestashopCategoryBaseParameters = [
            'name'              => $this->getCategoryLabel($category, $context['defaultLocale']),
            'is_active'         => 1,
            'include_in_menu'   => 1,
            'available_sort_by' => 1,
            'default_sort_by'   => 1,
        ];

        if (false === $context['urlKey']) {
            $prestashopCategoryBaseParameters['url_key'] = $this->generateUrlKey($category, $context['defaultLocale']);
        }

        if (null === $parentCategoryId) {
            throw new CategoryNotMappedException(
                sprintf(
                    'An error occured during the root category creation on Prestashop. The Prestashop '.
                    'connector was unable to find the mapped category "%s (%s)". Remember that you need to map your '.
                    'Prestashop root categories to Akeneo categories. All sub categories of %s will not be exported.',
                    $category->getLabel(),
                    $category->getCode(),
                    $category->getCode()
                )
            );
        } else {
            return [
                'prestashopCategory' => [
                    (string) $parentCategoryId,
                    $prestashopCategoryBaseParameters,
                    $context['defaultStoreView'],
                ],
                'pimCategory' => $category,
            ];
        }
    }

    /**
     * Get update normalized categories.
     *
     * @param CategoryInterface $category
     * @param array             $context
     *
     * @return array
     */
    protected function getNormalizedUpdateCategory(CategoryInterface $category, array $context)
    {
        $prestashopCategoryBaseParameters = [
            'name'              => $this->getCategoryLabel($category, $context['defaultLocale']),
            'available_sort_by' => 1,
            'default_sort_by'   => 1,
            'is_anchor'         => $context['is_anchor'],
            'position'          => $category->getLeft(),
        ];

        if (false === $context['urlKey']) {
            $prestashopCategoryBaseParameters['url_key'] = $this->generateUrlKey($category, $context['defaultLocale']);
        }

        return [
            $this->getPrestashopCategoryId($category, $context['prestashopUrl']),
            $prestashopCategoryBaseParameters,
            $context['defaultStoreView'],
        ];
    }

    /**
     * Get normalized variation category.
     *
     * @param CategoryInterface $category
     * @param string            $localeCode
     * @param string            $storeViewCode
     * @param boolean           $urlKey
     *
     * @return array
     */
    protected function getNormalizedVariationCategory(
        CategoryInterface $category,
        $localeCode,
        $storeViewCode,
        $urlKey = false
    ) {
        $prestashopCategoryData = [
            'name'              => $this->getCategoryLabel($category, $localeCode),
            'available_sort_by' => 1,
            'default_sort_by'   => 1,
        ];

        if (false === $urlKey) {
            $prestashopCategoryData['url_key'] = $this->generateUrlKey($category, $localeCode);
        }

        return [
            'prestashopCategory' => [
                null,
                $prestashopCategoryData,
                $storeViewCode,
            ],
            'pimCategory' => $category,
        ];
    }

    /**
     * Get move normalized categories.
     *
     * @param CategoryInterface $category
     * @param array             $context
     *
     * @return array
     */
    protected function getNormalizedMoveCategory(CategoryInterface $category, array $context)
    {
        $prestashopCategoryId = $this->getPrestashopCategoryId($category, $context['prestashopUrl']);

        $prestashopCategoryNewParentId = $this->categoryMappingManager->getIdFromCategory(
            $category->getParent(),
            $context['prestashopUrl'],
            $context['categoryMapping']
        );

        $previousCategories = $this->categoryRepository->getPrevSiblings($category);
        $previousCategory = end($previousCategories);

        $previousPrestashopCategoryId = null;
        if ($previousCategory && null !== $previousCategory) {
            $previousPrestashopCategoryId = $this->categoryMappingManager->getIdFromCategory(
                $previousCategory,
                $context['prestashopUrl'],
                $context['categoryMapping']
            );
        }

        return [
            $prestashopCategoryId,
            $prestashopCategoryNewParentId,
            $previousPrestashopCategoryId,
        ];
    }

    /**
     * Get category label.
     *
     * @param CategoryInterface $category
     * @param string            $localeCode
     *
     * @return string
     */
    protected function getCategoryLabel(CategoryInterface $category, $localeCode)
    {
        $category->setLocale($localeCode);

        return $category->getLabel();
    }

    /**
     * Test if the category has moved on prestashop side.
     *
     * @param CategoryInterface $category
     * @param array             $context
     *
     * @return boolean
     */
    protected function categoryHasMoved(CategoryInterface $category, $context)
    {
        $currentCategoryId = $this->getPrestashopCategoryId($category, $context['prestashopUrl']);
        $currentParentId   = $this->categoryMappingManager->getIdFromCategory(
            $category->getParent(),
            $context['prestashopUrl'],
            $context['categoryMapping']
        );

        return isset($context['prestashopCategories'][$currentCategoryId]) ?
            $context['prestashopCategories'][$currentCategoryId]['parent_id'] !== $currentParentId :
            true;
    }

    /**
     * Generate url key for category name and code
     * The code is included to make sure the url_key is unique, as required in Prestashop.
     *
     * @param CategoryInterface $category
     * @param string            $localeCode
     *
     * @return string
     */
    protected function generateUrlKey(CategoryInterface $category, $localeCode)
    {
        $code = $category->getCode();
        $label = $this->getCategoryLabel($category, $localeCode);

        $url = Urlizer::urlize($label.'-'.$code);

        return $url;
    }
}
