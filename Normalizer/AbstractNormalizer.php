<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Normalizer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\Exception\LocaleNotMatchedException;
use Pim\Bundle\PrestashopConnectorBundle\Mapper\MappingCollection;
use Pim\Bundle\CatalogBundle\Entity\Locale;

/**
 * A normalizer to transform a product entity into an array.
 *
 */
abstract class AbstractNormalizer implements NormalizerInterface
{
    /** @staticvar string */
    const PRESTASHOP_SIMPLE_PRODUCT_KEY       = 'simple';

    /** @staticvar string */
    const PRESTASHOP_CONFIGURABLE_PRODUCT_KEY = 'configurable';

    /** @staticvar string */
    const PRESTASHOP_GROUPED_PRODUCT_KEY      = 'grouped';

    /** @staticvar string */
    const PRESTASHOP_BUNDLE_PRODUCT_KEY       = 'bundle';

    /** @staticvar string */
    const PRESTASHOP_DOWNLOADABLE_PRODUCT_KEY = 'downloadable';

    /** @staticvar string */
    const PRESTASHOP_VIRTUAL_PRODUCT_KEY      = 'virtual';

    /** @staticvar string */
    const DATE_FORMAT                      = 'Y-m-d H:i:s';

    /** @staticvar string */
    const PRESTASHOP_FORMAT = 'PrestashopArray';

    /** @var array */
    protected $pimLocales;

    /** @var array */
    protected $supportedFormats = [self::PRESTASHOP_FORMAT];

    /** @var ChannelManager */
    protected $channelManager;

    /**
     * @param ChannelManager $channelManager
     */
    public function __construct(ChannelManager $channelManager)
    {
        $this->channelManager = $channelManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return in_array($format, $this->supportedFormats);
    }

    /**
     * Get all Pim locales for the given channel.
     *
     * @param string $channel
     *
     * @return array
     */
    protected function getPimLocales($channel)
    {
        if (!$this->pimLocales) {
            $this->pimLocales = $this->channelManager
                ->getChannelByCode($channel)
                ->getLocales();
        }

        return $this->pimLocales;
    }

    /**
     * Get the corresponding storeview for a given locale.
     *
     * @param string            $locale
     * @param array             $prestashopStoreViews
     * @param MappingCollection $storeViewMapping
     *
     * @return string
     */
    protected function getStoreViewForLocale($locale, $prestashopStoreViews, MappingCollection $storeViewMapping)
    {
        return $this->getStoreView($storeViewMapping->getTarget($locale), $prestashopStoreViews);
    }

    /**
     * Get the storeview for the given code.
     *
     * @param string $code
     * @param array  $prestashopStoreViews
     *
     * @return null|string
     */
    protected function getStoreView($code, $prestashopStoreViews)
    {
        foreach ($prestashopStoreViews as $prestashopStoreView) {
            if ($prestashopStoreView['code'] === strtolower($code)) {
                return $prestashopStoreView;
            }
        }
    }

    /**
     * Manage not found locales.
     *
     * @param Locale $locale
     *
     * @throws LocaleNotMatchedException
     */
    protected function localeNotFound(Locale $locale)
    {
        throw new LocaleNotMatchedException(
            sprintf(
                'No storeview found for "%s" locale. Please create a storeview named "%s" on your Prestashop or map '.
                'this locale to a storeview code. You can also disable this locale in your channel\'s settings if you '.
                'don\'t want to export it.',
                $locale->getCode(),
                $locale->getCode()
            )
        );
    }
}
