<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Normalizer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Pim\Bundle\CatalogBundle\Model\AbstractAttribute;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\Exception\InvalidAttributeNameException;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\Exception\AttributeTypeChangedException;
use Pim\Bundle\PrestashopConnectorBundle\Mapper\MappingCollection;

/**
 * A normalizer to transform a option entity into an array.
 *
 */
class AttributeNormalizer implements NormalizerInterface
{
    /** @staticvar string */
    const STORE_SCOPE    = 'store';

    /** @staticvar string */
    const GLOBAL_SCOPE   = 'global';

    /** @staticvar string */
    const PRESTASHOP_FORMAT = 'PrestashopArray';

    /** @var ProductValueNormalizer */
    protected $productValueNormalizer;

    /** @var array */
    protected $supportedFormats = [self::PRESTASHOP_FORMAT];

    /**
     * @param ProductValueNormalizer $productValueNormalizer
     */
    public function __construct(ProductValueNormalizer $productValueNormalizer)
    {
        $this->productValueNormalizer = $productValueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof AbstractAttribute && in_array($format, $this->supportedFormats);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($attribute, $format = null, array $context = [])
    {
        $normalizedAttribute = [
            'scope'                         => $this->getNormalizedScope($attribute),
            'is_unique'                     => '0',
            'is_required'                   => $this->getNormalizedRequired($attribute),
            'apply_to'                      => '',
            'is_configurable'               => $this->getNormalizedConfigurable($attribute, $context['axisAttributes']),
            'additional_fields'             => [],
            'frontend_label'                => $this->getNormalizedLabels(
                $attribute,
                $context['prestashopStoreViews'],
                $context['defaultLocale'],
                $context['storeViewMapping'],
                $context['attributeCodeMapping']
            ),
        ];

        $mappedAttributeType = $this->getNormalizedType($attribute);

        if ($context['create']) {
            $normalizedAttribute = array_merge(
                [
                    'attribute_code' => $this->getNormalizedCode($attribute, $context['attributeCodeMapping']),
                    'frontend_input' => $mappedAttributeType,
                ],
                $normalizedAttribute
            );
        } else {
            $prestashopAttributeCode = strtolower($context['attributeCodeMapping']->getTarget($attribute->getCode()));
            $prestashopAttributeType = $context['prestashopAttributes'][$prestashopAttributeCode]['type'];
            if ($mappedAttributeType !== $prestashopAttributeType &&
                !in_array($prestashopAttributeCode, $this->getIgnoredAttributesForTypeChangeDetection())) {
                throw new AttributeTypeChangedException(
                    sprintf(
                        'The type for the attribute "%s" has changed (Is "%s" in Prestashop and is %s in Akeneo PIM. '.
                        'This operation is not permitted by Prestashop. Please delete it first on Prestashop and try to '.
                        'export again.',
                        $attribute->getCode(),
                        $context['prestashopAttributes'][$prestashopAttributeCode]['type'],
                        $mappedAttributeType
                    )
                );
            }

            $normalizedAttribute = [
                $prestashopAttributeCode,
                $normalizedAttribute,
            ];
        }

        return $normalizedAttribute;
    }

    /**
     * Get normalized type for attribute.
     *
     * @param AbstractAttribute $attribute
     *
     * @return string
     */
    protected function getNormalizedType(AbstractAttribute $attribute)
    {
        return isset($this->getTypeMapping()[$attribute->getAttributeType()]) ?
            $this->getTypeMapping()[$attribute->getAttributeType()] :
            'text';
    }

    /**
     * Get attribute type mapping.
     *
     * @return string[]
     */
    protected function getTypeMapping()
    {
        return [
            'pim_catalog_identifier'       => 'text',
            'pim_catalog_text'             => 'text',
            'pim_catalog_textarea'         => 'textarea',
            'pim_catalog_multiselect'      => 'multiselect',
            'pim_catalog_simpleselect'     => 'select',
            'pim_catalog_price_collection' => 'price',
            'pim_catalog_number'           => 'text',
            'pim_catalog_boolean'          => 'boolean',
            'pim_catalog_date'             => 'date',
            'pim_catalog_file'             => 'text',
            'pim_catalog_image'            => 'text',
            'pim_catalog_metric'           => 'text',
        ];
    }

    /**
     * Get normalized code for attribute.
     *
     * @param AbstractAttribute $attribute
     * @param MappingCollection $attributeMapping
     *
     * @throws InvalidAttributeNameException If attribute name is not valid
     *
     * @return string
     */
    protected function getNormalizedCode(AbstractAttribute $attribute, MappingCollection $attributeMapping)
    {
        $attributeCode = strtolower($attributeMapping->getTarget($attribute->getCode()));

        if (preg_match('/^[a-z][a-z_0-9]{0,30}$/', $attributeCode) === 0) {
            throw new InvalidAttributeNameException(
                sprintf(
                    'The attribute "%s" have a code that is not compatible with Prestashop. Please use only'.
                    ' lowercase letters (a-z), numbers (0-9) or underscore(_). First caracter should also'.
                    ' be a letter and your attribute codelength must be under 30 characters',
                    $attribute->getCode()
                )
            );
        }

        return $attributeCode;
    }

    /**
     * Get normalized scope for attribute.
     *
     * @param AbstractAttribute $attribute
     *
     * @return string
     */
    protected function getNormalizedScope(AbstractAttribute $attribute)
    {
        return $attribute->isLocalizable() ? self::STORE_SCOPE : self::GLOBAL_SCOPE;
    }

    /**
     * Get normalized unique value for attribute.
     *
     * @param AbstractAttribute $attribute
     *
     * @return string
     */
    protected function getNormalizedUnique(AbstractAttribute $attribute)
    {
        return $attribute->isUnique() ? '1' : '0';
    }

    /**
     * Get normalized is required for attribute.
     *
     * @param AbstractAttribute $attribute
     *
     * @return string
     */
    protected function getNormalizedRequired(AbstractAttribute $attribute)
    {
        return $attribute->isRequired() ? '1' : '0';
    }

    /**
     * Get normalized configurable for attribute.
     *
     * @param AbstractAttribute $attribute
     * @param array             $axisAttributes
     *
     * @return string
     */
    protected function getNormalizedConfigurable(AbstractAttribute $attribute, array $axisAttributes)
    {
        return ($attribute->getAttributeType() === 'pim_catalog_simpleselect') &&
            in_array($attribute->getCode(), $axisAttributes)
            ? '1'
            : '0';
    }

    /**
     * Get normalized labels for attribute.
     *
     * @param AbstractAttribute $attribute
     * @param array             $prestashopStoreViews
     * @param string            $defaultLocale
     * @param MappingCollection $storeViewMapping
     * @param MappingCollection $attributeMapping
     *
     * @return array
     */
    protected function getNormalizedLabels(
        AbstractAttribute $attribute,
        array $prestashopStoreViews,
        $defaultLocale,
        MappingCollection $storeViewMapping,
        MappingCollection $attributeMapping
    ) {
        $localizedLabels = [];

        foreach ($prestashopStoreViews as $prestashopStoreView) {
            $localeCode = $storeViewMapping->getSource($prestashopStoreView['code']);

            $localizedLabels[] = [
                'store_id' => $prestashopStoreView['store_id'],
                'label'    => $this->getAttributeTranslation($attribute, $localeCode, $defaultLocale),
            ];
        }

        return array_merge(
            [
                [
                    'store_id' => 0,
                    'label'    => strtolower($attributeMapping->getTarget($attribute->getCode())),
                ],
            ],
            $localizedLabels
        );
    }

    /**
     * Get attribute translation for given locale code.
     *
     * @param AbstractAttribute $attribute
     * @param string            $localeCode
     * @param string            $defaultLocale
     *
     * @return mixed
     */
    protected function getAttributeTranslation(AbstractAttribute $attribute, $localeCode, $defaultLocale)
    {
        foreach ($attribute->getTranslations() as $translation) {
            if (strtolower($translation->getLocale()) == strtolower($localeCode) &&
                $translation->getLabel() !== null) {
                return $translation->getLabel();
            }
        }

        if ($localeCode === $defaultLocale) {
            return $attribute->getCode();
        } else {
            return $this->getAttributeTranslation($attribute, $defaultLocale, $defaultLocale);
        }
    }

    /**
     * Get all ignored attribute for type change detection.
     *
     * @return string[]
     */
    protected function getIgnoredAttributesForTypeChangeDetection()
    {
        return [
            'tax_class_id',
            'weight',
        ];
    }
}
