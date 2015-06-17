<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Guesser;

use Pim\Bundle\PrestashopConnectorBundle\Filter\ExportableLocaleFilter;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\AbstractNormalizer;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\ProductNormalizer;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\ProductNormalizer16;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\ConfigurableNormalizer;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopSoapClientParameters;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopSoapClientFactory;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\CatalogBundle\Manager\MediaManager;
use Pim\Bundle\PrestashopConnectorBundle\Manager\PriceMappingManager;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\ProductNormalizerInterface;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\ProductValueNormalizer;
use Pim\Bundle\PrestashopConnectorBundle\Manager\CategoryMappingManager;
use Pim\Bundle\PrestashopConnectorBundle\Manager\AssociationTypeManager;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\CategoryNormalizer;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\FamilyNormalizer;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\OptionNormalizer;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\AttributeNormalizer;

/**
 * A prestashop guesser to get the proper normalizer.
 *
 */
class NormalizerGuesser extends AbstractGuesser
{
    /** @var PrestashopSoapClientFactory */
    protected $prestashopSoapClientFactory;

    /** @var ChannelManager */
    protected $channelManager;

    /** @var MediaManager */
    protected $mediaManager;

    /** @var ProductValueNormalizer */
    protected $productValueNormalizer;

    /** @var AssociationTypeManager */
    protected $associationTypeManager;

    /** @var \Pim\Bundle\PrestashopConnectorBundle\Normalizer\AttributeNormalizer */
    protected $attributeNormalizer;

    /** @var \Pim\Bundle\PrestashopConnectorBundle\Normalizer\CategoryNormalizer */
    protected $categoryNormalizer;

    /** @var \Pim\Bundle\PrestashopConnectorBundle\Normalizer\FamilyNormalizer */
    protected $familyNormalizer;

    /** @var \Pim\Bundle\PrestashopConnectorBundle\Normalizer\OptionNormalizer */
    protected $optionNormalizer;

    /** @var ExportableLocaleFilter */
    protected $localeFilter;

    /**
     * @param PrestashopSoapClientFactory $prestashopSoapClientFactory
     * @param ChannelManager           $channelManager
     * @param MediaManager             $mediaManager
     * @param ProductValueNormalizer   $productValueNormalizer
     * @param CategoryMappingManager   $categoryMappingManager
     * @param AssociationTypeManager   $associationTypeManager
     * @param CategoryNormalizer       $categoryNormalizer
     * @param FamilyNormalizer         $familyNormalizer
     * @param OptionNormalizer         $optionNormalizer
     * @param ExportableLocaleFilter   $localeFilter
     */
    public function __construct(
        PrestashopSoapClientFactory $prestashopSoapClientFactory,
        ChannelManager $channelManager,
        MediaManager $mediaManager,
        ProductValueNormalizer $productValueNormalizer,
        CategoryMappingManager $categoryMappingManager,
        AssociationTypeManager $associationTypeManager,
        AttributeNormalizer $attributeNormalizer,
        CategoryNormalizer $categoryNormalizer,
        FamilyNormalizer $familyNormalizer,
        OptionNormalizer $optionNormalizer,
        ExportableLocaleFilter $localeFilter
    ) {
        $this->prestashopSoapClientFactory = $prestashopSoapClientFactory;
        $this->channelManager           = $channelManager;
        $this->mediaManager             = $mediaManager;
        $this->productValueNormalizer   = $productValueNormalizer;
        $this->categoryMappingManager   = $categoryMappingManager;
        $this->associationTypeManager   = $associationTypeManager;
        $this->attributeNormalizer      = $attributeNormalizer;
        $this->categoryNormalizer       = $categoryNormalizer;
        $this->familyNormalizer         = $familyNormalizer;
        $this->optionNormalizer         = $optionNormalizer;
        $this->localeFilter             = $localeFilter;
    }

    /**
     * Get the product normalizer corresponding to the given Prestashop parameters.
     *
     * @param PrestashopSoapClientParameters $clientParameters
     * @param boolean                     $enabled
     * @param boolean                     $visibility
     * @param boolean                     $variantMemberVisibility
     * @param string                      $currencyCode
     *
     * @throws NotSupportedVersionException If the prestashop version is not supported
     *
     * @return AbstractNormalizer
     */
    public function getProductNormalizer(
        PrestashopSoapClientParameters $clientParameters,
        $enabled,
        $visibility,
        $variantMemberVisibility,
        $currencyCode
    ) {
        $client         = $this->prestashopSoapClientFactory->getPrestashopSoapClient($clientParameters);
        $prestashopVersion = $this->getPrestashopVersion($client);

        switch ($prestashopVersion) {
            case AbstractGuesser::PRESTASHOP_VERSION_1_14:
            case AbstractGuesser::PRESTASHOP_VERSION_1_13:
            case AbstractGuesser::PRESTASHOP_VERSION_1_12:
            case AbstractGuesser::PRESTASHOP_VERSION_1_11:
            case AbstractGuesser::PRESTASHOP_VERSION_1_9:
            case AbstractGuesser::PRESTASHOP_VERSION_1_8:
            case AbstractGuesser::PRESTASHOP_VERSION_1_7:
                return new ProductNormalizer(
                    $this->channelManager,
                    $this->mediaManager,
                    $this->productValueNormalizer,
                    $this->categoryMappingManager,
                    $this->associationTypeManager,
                    $this->localeFilter,
                    $enabled,
                    $visibility,
                    $variantMemberVisibility,
                    $currencyCode,
                    $clientParameters->getSoapUrl()
                );
            case AbstractGuesser::PRESTASHOP_VERSION_1_6:
                return new ProductNormalizer16(
                    $this->channelManager,
                    $this->mediaManager,
                    $this->productValueNormalizer,
                    $this->categoryMappingManager,
                    $this->associationTypeManager,
                    $this->localeFilter,
                    $enabled,
                    $visibility,
                    $variantMemberVisibility,
                    $currencyCode,
                    $clientParameters->getSoapUrl()
                );
            default:
                throw new NotSupportedVersionException(AbstractGuesser::PRESTASHOP_VERSION_NOT_SUPPORTED_MESSAGE);
        }
    }

    /**
     * Get the configurable normalizer corresponding to the given Prestashop parameters.
     *
     * @param PrestashopSoapClientParameters $clientParameters
     * @param ProductNormalizerInterface  $productNormalizer
     * @param PriceMappingManager         $priceMappingManager
     * @param boolean                     $visibility
     *
     * @return ConfigurableNormalizer
     *
     * @throws NotSupportedVersionException
     */
    public function getConfigurableNormalizer(
        PrestashopSoapClientParameters $clientParameters,
        ProductNormalizerInterface $productNormalizer,
        PriceMappingManager $priceMappingManager,
        $visibility
    ) {
        $client         = $this->prestashopSoapClientFactory->getPrestashopSoapClient($clientParameters);
        $prestashopVersion = $this->getPrestashopVersion($client);

        switch ($prestashopVersion) {
            case AbstractGuesser::PRESTASHOP_VERSION_1_14:
            case AbstractGuesser::PRESTASHOP_VERSION_1_13:
            case AbstractGuesser::PRESTASHOP_VERSION_1_12:
            case AbstractGuesser::PRESTASHOP_VERSION_1_11:
            case AbstractGuesser::PRESTASHOP_VERSION_1_9:
            case AbstractGuesser::PRESTASHOP_VERSION_1_8:
            case AbstractGuesser::PRESTASHOP_VERSION_1_7:
            case AbstractGuesser::PRESTASHOP_VERSION_1_6:
                return new ConfigurableNormalizer(
                    $this->channelManager,
                    $productNormalizer,
                    $priceMappingManager,
                    $visibility
                );
            default:
                throw new NotSupportedVersionException(AbstractGuesser::PRESTASHOP_VERSION_NOT_SUPPORTED_MESSAGE);
        }
    }

    /**
     * Get the Category normalizer corresponding to the given Prestashop parameters.
     *
     * @param PrestashopSoapClientParameters $clientParameters
     *
     * @return AbstractNormalizer
     *
     * @throws NotSupportedVersionException
     */
    public function getCategoryNormalizer(PrestashopSoapClientParameters $clientParameters)
    {
        $client         = $this->prestashopSoapClientFactory->getPrestashopSoapClient($clientParameters);
        $prestashopVersion = $this->getPrestashopVersion($client);

        switch ($prestashopVersion) {
            case AbstractGuesser::PRESTASHOP_VERSION_1_14:
            case AbstractGuesser::PRESTASHOP_VERSION_1_13:
            case AbstractGuesser::PRESTASHOP_VERSION_1_12:
            case AbstractGuesser::PRESTASHOP_VERSION_1_11:
            case AbstractGuesser::PRESTASHOP_VERSION_1_9:
            case AbstractGuesser::PRESTASHOP_VERSION_1_8:
            case AbstractGuesser::PRESTASHOP_VERSION_1_7:
            case AbstractGuesser::PRESTASHOP_VERSION_1_6:
                return $this->categoryNormalizer;
            default:
                throw new NotSupportedVersionException(AbstractGuesser::PRESTASHOP_VERSION_NOT_SUPPORTED_MESSAGE);
        }
    }

    /**
     * Get the option normalizer corresponding to the given Prestashop parameters.
     *
     * @param PrestashopSoapClientParameters $clientParameters
     *
     * @return AbstractNormalizer
     *
     * @throws NotSupportedVersionException
     */
    public function getOptionNormalizer(PrestashopSoapClientParameters $clientParameters)
    {
        $client         = $this->prestashopSoapClientFactory->getPrestashopSoapClient($clientParameters);
        $prestashopVersion = $this->getPrestashopVersion($client);

        switch ($prestashopVersion) {
            case AbstractGuesser::PRESTASHOP_VERSION_1_14:
            case AbstractGuesser::PRESTASHOP_VERSION_1_13:
            case AbstractGuesser::PRESTASHOP_VERSION_1_12:
            case AbstractGuesser::PRESTASHOP_VERSION_1_11:
            case AbstractGuesser::PRESTASHOP_VERSION_1_9:
            case AbstractGuesser::PRESTASHOP_VERSION_1_8:
            case AbstractGuesser::PRESTASHOP_VERSION_1_7:
            case AbstractGuesser::PRESTASHOP_VERSION_1_6:
                return $this->optionNormalizer;
            default:
                throw new NotSupportedVersionException(AbstractGuesser::PRESTASHOP_VERSION_NOT_SUPPORTED_MESSAGE);
        }
    }

    /**
     * Get the attribute normalizer corresponding to the given Prestashop parameters.
     *
     * @param PrestashopSoapClientParameters $clientParameters
     *
     * @return AbstractNormalizer
     *
     * @throws NotSupportedVersionException
     */
    public function getAttributeNormalizer(PrestashopSoapClientParameters $clientParameters)
    {
        $client         = $this->prestashopSoapClientFactory->getPrestashopSoapClient($clientParameters);
        $prestashopVersion = $this->getPrestashopVersion($client);

        switch ($prestashopVersion) {
            case AbstractGuesser::PRESTASHOP_VERSION_1_14:
            case AbstractGuesser::PRESTASHOP_VERSION_1_13:
            case AbstractGuesser::PRESTASHOP_VERSION_1_12:
            case AbstractGuesser::PRESTASHOP_VERSION_1_11:
            case AbstractGuesser::PRESTASHOP_VERSION_1_9:
            case AbstractGuesser::PRESTASHOP_VERSION_1_8:
            case AbstractGuesser::PRESTASHOP_VERSION_1_7:
            case AbstractGuesser::PRESTASHOP_VERSION_1_6:
                return $this->attributeNormalizer;
            default:
                throw new NotSupportedVersionException(AbstractGuesser::PRESTASHOP_VERSION_NOT_SUPPORTED_MESSAGE);
        }
    }

    /**
     * Get the family normalizer corresponding to the given Prestashop parameters.
     *
     * @param PrestashopSoapClientParameters $clientParameters
     *
     * @throws NotSupportedVersionException
     *
     * @return FamilyNormalizer
     */
    public function getFamilyNormalizer(PrestashopSoapClientParameters $clientParameters)
    {
        $client         = $this->prestashopSoapClientFactory->getPrestashopSoapClient($clientParameters);
        $prestashopVersion = $this->getPrestashopVersion($client);

        switch ($prestashopVersion) {
            case AbstractGuesser::PRESTASHOP_VERSION_1_14:
            case AbstractGuesser::PRESTASHOP_VERSION_1_13:
            case AbstractGuesser::PRESTASHOP_VERSION_1_12:
            case AbstractGuesser::PRESTASHOP_VERSION_1_11:
            case AbstractGuesser::PRESTASHOP_VERSION_1_9:
            case AbstractGuesser::PRESTASHOP_VERSION_1_8:
            case AbstractGuesser::PRESTASHOP_VERSION_1_7:
            case AbstractGuesser::PRESTASHOP_VERSION_1_6:
                return $this->familyNormalizer;
            default:
                throw new NotSupportedVersionException(AbstractGuesser::PRESTASHOP_VERSION_NOT_SUPPORTED_MESSAGE);
        }
    }
}
