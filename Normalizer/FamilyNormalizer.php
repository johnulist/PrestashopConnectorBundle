<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Normalizer;

use Pim\Bundle\CatalogBundle\Entity\Family;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * A normalizer to transform a family entity into an array.
 *
 */
class FamilyNormalizer implements NormalizerInterface
{
    /** @staticvar string */
    const PRESTASHOP_FORMAT = 'PrestashopArray';

    /** @var array */
    protected $supportedFormats = [self::PRESTASHOP_FORMAT];

    /**
     * {@inheritdoc}
     */
    public function normalize($family, $format = null, array $context = [])
    {
        return ['attributeSetName' => $family->getCode()];
    }

    /**
     * Checks whether the given class is supported for normalization by this normalizer.
     *
     * @param mixed  $data   Data to normalize.
     * @param string $format The format being (de-)serialized from or into.
     *
     * @return boolean
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Family && in_array($format, $this->supportedFormats);
    }
}
