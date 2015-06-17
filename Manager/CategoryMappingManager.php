<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Pim\Bundle\CatalogBundle\Model\CategoryInterface;
use Pim\Bundle\PrestashopConnectorBundle\Mapper\MappingCollection;

/**
 * Category mapping manager.
 *
 */
class CategoryMappingManager
{
    /** @var \Doctrine\Common\Persistence\ObjectManager */
    protected $objectManager;

    /** @var string */
    protected $className;

    /**
     * @param ObjectManager $objectManager
     * @param string        $className
     */
    public function __construct(ObjectManager $objectManager, $className)
    {
        $this->objectManager = $objectManager;
        $this->className     = $className;
    }

    /**
     * Get category from id and Prestashop url.
     *
     * @param int    $id
     * @param string $prestashopUrl
     *
     * @return CategoryInterface|null
     */
    public function getCategoryFromId($id, $prestashopUrl)
    {
        $prestashopCategoryMapping = $this->getEntityRepository()->findOneBy(
            [
                'prestashopCategoryId' => $id,
                'prestashopUrl'        => $prestashopUrl,
            ]
        );

        return $prestashopCategoryMapping ? $prestashopCategoryMapping->getCategory() : null;
    }

    /**
     * Get id from category and Prestashop url.
     *
     * @param CategoryInterface $category
     * @param string            $prestashopUrl
     * @param MappingCollection $categoryMapping
     *
     * @return int|null
     */
    public function getIdFromCategory(
        CategoryInterface $category,
        $prestashopUrl,
        MappingCollection $categoryMapping = null
    ) {
        if ($categoryMapping &&
            ($categoryId = $categoryMapping->getTarget($category->getCode())) != $category->getCode()
        ) {
            return $categoryId;
        } else {
            $categoryMapping = $this->getEntityRepository()->findOneBy(
                [
                    'category'   => $category,
                    'prestashopUrl' => $prestashopUrl,
                ]
            );

            return $categoryMapping ? $categoryMapping->getPrestashopCategoryId() : null;
        }
    }

    /**
     * Register a new category mapping.
     *
     * @param CategoryInterface $pimCategory
     * @param int               $prestashopCategoryId
     * @param string            $prestashopUrl
     */
    public function registerCategoryMapping(
        CategoryInterface $pimCategory,
        $prestashopCategoryId,
        $prestashopUrl
    ) {
        $categoryMapping = $this->getEntityRepository()->findOneBy([
            'category'   => $pimCategory,
            'prestashopUrl' => $prestashopUrl,
        ]);
        $prestashopCategoryMapping = new $this->className();

        if ($categoryMapping) {
            $prestashopCategoryMapping = $categoryMapping;
        }

        $prestashopCategoryMapping->setCategory($pimCategory);
        $prestashopCategoryMapping->setPrestashopCategoryId($prestashopCategoryId);
        $prestashopCategoryMapping->setPrestashopUrl($prestashopUrl);

        $this->objectManager->persist($prestashopCategoryMapping);
        $this->objectManager->flush();
    }

    /**
     * Does the given prestashop category exist in pim ?
     *
     * @param string $categoryId
     * @param string $prestashopUrl
     *
     * @return boolean
     */
    public function prestashopCategoryExists($categoryId, $prestashopUrl)
    {
        return $this->getCategoryFromId($categoryId, $prestashopUrl) !== null;
    }

    /**
     * Get the entity manager.
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getEntityRepository()
    {
        return $this->objectManager->getRepository($this->className);
    }
}
