<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Cleaner;

use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopRestClientParametersRegistry;
use Pim\Bundle\PrestashopConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\PrestashopConnectorBundle\Manager\CategoryMappingManager;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\RestCallException;
use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;

/**
 * Prestashop category cleaner.
 *
 */
class CategoryCleaner extends Cleaner
{
    /** @staticvar string */
    const SOAP_FAULT_NO_CATEGORY = '102';

    /** @var CategoryMappingManager */
    protected $categoryMappingManager;

    /**
     * @param WebserviceGuesser                   $webserviceGuesser
     * @param CategoryMappingManager              $categoryMappingManager
     * @param PrestashopRestClientParametersRegistry $clientParametersRegistry
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        CategoryMappingManager $categoryMappingManager,
        PrestashopRestClientParametersRegistry $clientParametersRegistry
    ) {
        parent::__construct($webserviceGuesser, $clientParametersRegistry);

        $this->categoryMappingManager = $categoryMappingManager;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        parent::beforeExecute();

        $prestashopCategories = $this->webservice->getCategoriesStatus();

        foreach ($prestashopCategories as $category) {
            if (!$this->categoryMappingManager->prestashopCategoryExists($category['category_id'], $this->getPrestashopUrl()) &&
                !(
                    $category['level'] === '0' ||
                    $category['level'] === '1'
                )
            ) {
                try {
                    $this->handleCategoryNotInPimAnymore($category);
                } catch (RestCallException $e) {
                    throw new InvalidItemException($e->getMessage(), [json_encode($category)]);
                }
            }
        }
    }

    /**
     * Handle deletion or disabling of categories that are not in PIM anymore.
     *
     * @param array $category
     *
     * @throws InvalidItemException
     * @throws RestCallException
     */
    protected function handleCategoryNotInPimAnymore(array $category)
    {
        if ($this->notInPimAnymoreAction === self::DISABLE) {
            $this->webservice->disableCategory($category['category_id']);
            $this->stepExecution->incrementSummaryInfo('category_disabled');
        } elseif ($this->notInPimAnymoreAction === self::DELETE) {
            try {
                $this->webservice->deleteCategory($category['category_id']);
                $this->stepExecution->incrementSummaryInfo('category_deleted');
            } catch (RestCallException $e) {
                if (static::SOAP_FAULT_NO_CATEGORY === $e->getPrevious()->faultcode) {
                    throw new InvalidItemException(
                        $e->getMessage(),
                        [json_encode($category)],
                        [$e]
                    );
                } else {
                    throw $e;
                }
            }
        }
    }
}
