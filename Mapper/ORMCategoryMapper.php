<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Mapper;

use Pim\Bundle\PrestashopConnectorBundle\Manager\SimpleMappingManager;
use Pim\Bundle\PrestashopConnectorBundle\Validator\Constraints\HasValidCredentialsValidator;
use Pim\Bundle\CatalogBundle\Manager\CategoryManager;
use Pim\Bundle\CatalogBundle\Model\CategoryInterface;

/**
 * Prestashop category mapper.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ORMCategoryMapper extends ORMPimMapper
{
    /** @var CategoryManager */
    protected $categoryManager;

    /**
     * @param HasValidCredentialsValidator $hasValidCredentialsValidator
     * @param SimpleMappingManager         $simpleMappingManager
     * @param string                       $rootIdentifier
     * @param CategoryManager              $categoryManager
     */
    public function __construct(
        HasValidCredentialsValidator $hasValidCredentialsValidator,
        SimpleMappingManager $simpleMappingManager,
        $rootIdentifier,
        CategoryManager $categoryManager
    ) {
        parent::__construct($hasValidCredentialsValidator, $simpleMappingManager, $rootIdentifier);

        $this->categoryManager = $categoryManager;
    }

    /**
     * @param CategoryInterface $category
     *
     * @return array
     */
    public function getAllSources(CategoryInterface $category = null)
    {
        $sources = [];

        if ($this->isValid()) {
            $categories = $category === null ? $this->categoryManager->getTrees() : $category->getChildren();

            foreach ($categories as $category) {
                $sources[] = [
                    'id'   => $category->getCode(),
                    'text' => sprintf('%s (%s)', $category->getLabel(), $category->getCode()),
                ];

                $sources = array_merge($sources, $this->getAllSources($category));
            }
        }

        return $sources;
    }
}
