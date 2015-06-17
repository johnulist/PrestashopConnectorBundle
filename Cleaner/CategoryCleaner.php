<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Cleaner;

use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopSoapClientParametersRegistry;
use Pim\Bundle\PrestashopConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\PrestashopConnectorBundle\Manager\CategoryMappingManager;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\SoapCallException;
use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;

/**
 * Prestashop category cleaner.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
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
     * @param PrestashopSoapClientParametersRegistry $clientParametersRegistry
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        CategoryMappingManager $categoryMappingManager,
        PrestashopSoapClientParametersRegistry $clientParametersRegistry
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
            if (!$this->categoryMappingManager->prestashopCategoryExists($category['category_id'], $this->getSoapUrl()) &&
                !(
                    $category['level'] === '0' ||
                    $category['level'] === '1'
                )
            ) {
                try {
                    $this->handleCategoryNotInPimAnymore($category);
                } catch (SoapCallException $e) {
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
     * @throws SoapCallException
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
            } catch (SoapCallException $e) {
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
