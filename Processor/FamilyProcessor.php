<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Processor;

use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Pim\Bundle\CatalogBundle\Entity\Family;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\AbstractNormalizer;
use Pim\Bundle\PrestashopConnectorBundle\Normalizer\Exception\NormalizeException;
use Pim\Bundle\TransformBundle\Normalizer\Flat\FamilyNormalizer;

/**
 * Prestashop family processor.
 *
 * @author    Olivier Soulet <olivier.soulet@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class FamilyProcessor extends AbstractProcessor
{
    /** @var FamilyNormalizer */
    protected $familyNormalizer;

    /** @var array */
    protected $globalContext;

    /**
     * {@inheritdoc}
     */
    protected function beforeExecute()
    {
        parent::beforeExecute();

        $prestashopStoreViews = $this->webservice->getStoreViewsList();

        $this->familyNormalizer = $this->normalizerGuesser->getFamilyNormalizer($this->getClientParameters());
        $this->globalContext['prestashopFamilies']   = $this->webservice->getAttributeSetList();
        $this->globalContext['prestashopStoreViews'] = $prestashopStoreViews;
        $this->globalContext['defaultStoreView']  = $this->getDefaultStoreView();
    }

    /**
     * {@inheritdoc}
     */
    public function process($family)
    {
        $this->beforeExecute();
        $result = [];

        $result['family_object']        = $family;
        $result['attributes_in_family'] = $family->getAttributes();
        // AttributeSet
        if (!$this->prestashopAttributeSetExists($family, $this->globalContext['prestashopFamilies'])) {
            $result['families_to_create'] = $this->normalizeFamily($family, $this->globalContext);
        }

        return $result;
    }

    /**
     * Test if an attribute set exist on prestashop.
     *
     * @param Family $family               Family of attribute
     * @param array  $prestashopAttributesSet Attribute sets from prestashop
     *
     * @return boolean Return true if the family exist on prestashop
     */
    protected function prestashopAttributeSetExists(Family $family, array $prestashopAttributesSet)
    {
        return array_key_exists($family->getCode(), $prestashopAttributesSet);
    }

    /**
     * Normalize the given family.
     *
     * @param Family $family  Family of attribute
     * @param array  $context
     *
     * @throws InvalidItemException If a problem occurred with the normalizer
     *
     * @return array
     */
    protected function normalizeFamily(Family $family, array $context)
    {
        try {
            $processedItem = $this->familyNormalizer->normalize(
                $family,
                AbstractNormalizer::MAGENTO_FORMAT,
                $context
            );
        } catch (NormalizeException $e) {
            throw new InvalidItemException($e->getMessage(), [$family]);
        }

        return $processedItem;
    }
}
