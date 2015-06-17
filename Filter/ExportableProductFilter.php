<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Filter;

use Pim\Bundle\CatalogBundle\Entity\Channel;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;

/**
 * Filter exportable products in terms of channel and completeness.
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ExportableProductFilter
{
    /**
     * Returns ready to export variant group products.
     *
     * @param Channel                                                    $channel
     * @param \Doctrine\Common\Collections\Collection|ProductInterface[] $products
     *
     * @return ProductInterface[]
     */
    public function apply(Channel $channel, $products)
    {
        $exportableProducts = [];
        $rootCategoryId     = $channel->getCategory()->getId();

        foreach ($products as $product) {
            $productCategories = $product->getCategories()->toArray();
            if ($this->isProductComplete($product, $channel) &&
                false !== $productCategories &&
                $this->doesProductBelongToChannel($productCategories, $rootCategoryId)
            ) {
                $exportableProducts[] = $product;
            }
        }

        return $exportableProducts;
    }

    /**
     * Is the given product complete for the given channel?
     *
     * @param ProductInterface $product
     * @param Channel          $channel
     *
     * @return bool
     */
    protected function isProductComplete(ProductInterface $product, Channel $channel)
    {
        $completenesses = $product->getCompletenesses()->toArray();
        foreach ($completenesses as $completeness) {
            if ($completeness->getChannel()->getId() === $channel->getId() &&
                $completeness->getRatio() === 100
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compute the belonging of a product to a channel.
     * Validating one of its categories has the same root as the channel root category.
     *
     * @param \ArrayIterator|array $productCategories
     * @param int                  $rootCategoryId
     *
     * @return bool
     */
    protected function doesProductBelongToChannel($productCategories, $rootCategoryId)
    {
        foreach ($productCategories as $category) {
            if ($category->getRoot() === $rootCategoryId) {
                return true;
            }
        }

        return false;
    }
}
