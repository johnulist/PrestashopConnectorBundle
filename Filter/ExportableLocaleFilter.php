<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Filter;

use Pim\Bundle\CatalogBundle\Entity\Channel;
use Pim\Bundle\CatalogBundle\Entity\Locale;
use Pim\Bundle\CatalogBundle\Model\AbstractCompleteness;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;

/**
 * Filter which locale is exportable for a given product and a given channel.
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ExportableLocaleFilter
{
    /**
     * @param ProductInterface $product
     * @param Channel          $locales
     *
     * @return Locale[]
     */
    public function apply(ProductInterface $product, Channel $channel)
    {
        $exportableLocales = [];
        foreach ($product->getCompletenesses() as $completeness) {
            if ($this->isComplete($completeness) && $this->isInChannel($completeness, $channel)) {
                $exportableLocales[] = $completeness->getLocale();
            }
        }

        return $exportableLocales;
    }

    /**
     * Checks if the given completeness has a ratio equal to 100.
     *
     * @param AbstractCompleteness $completeness
     *
     * @return bool
     */
    protected function isComplete(AbstractCompleteness $completeness)
    {
        if (100 === $completeness->getRatio()) {
            return true;
        }

        return false;
    }

    /**
     * Checks if the given completeness is in the given channel.
     *
     * @param AbstractCompleteness $completeness
     * @param Channel              $channel
     *
     * @return bool
     */
    protected function isInChannel(AbstractCompleteness $completeness, Channel $channel)
    {
        if ($completeness->getChannel()->getId() === $channel->getId()) {
            return true;
        }

        return false;
    }
}
