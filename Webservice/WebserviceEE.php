<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Webservice;

/**
 * A prestashop soap client to abstract interaction with the prestashop ee api.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class WebserviceEE extends Webservice
{
    /**
     * Get options for the given attribute.
     *
     * @param string $attributeCode
     *
     * @return array the formated options for the given attribute
     */
    public function getAttributeOptions($attributeCode)
    {
        if (!in_array($attributeCode, $this->getIgnoredAttributes())) {
            $options = $this->client->call(
                self::SOAP_ACTION_ATTRIBUTE_OPTION_LIST,
                [$attributeCode, self::ADMIN_STOREVIEW]
            );
        } else {
            $options = [];
        }

        $formatedOptions = [];

        foreach ($options as $option) {
            $formatedOptions[$option['label']] = $option['value'];
        }

        return $formatedOptions;
    }

    /**
     * @return array
     */
    protected function getIgnoredAttributes()
    {
        return [
            'is_returnable',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function updateProductPart($productPart)
    {
        $productPart = $this->removeNonUpdatePart($productPart);
        $this->client->addCall(
            [self::SOAP_ACTION_CATALOG_PRODUCT_UPDATE, $productPart]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function sendProduct($productPart)
    {
        if (count($productPart) === static::CREATE_PRODUCT_SIZE ||
            count($productPart) === static::CREATE_CONFIGURABLE_SIZE &&
            $productPart[static::CREATE_CONFIGURABLE_SIZE - 1] != 'sku'
        ) {
            $this->client->addCall([static::SOAP_ACTION_CATALOG_PRODUCT_CREATE, $productPart]);
        } else {
            $productPart = $this->removeNonUpdatePart($productPart);
            $this->client->addCall([static::SOAP_ACTION_CATALOG_PRODUCT_UPDATE, $productPart]);
        }
    }

    /**
     * Cleanup part of the product data that should not be sent as
     * update part.
     *
     * @param array $productPart
     *
     * @return array
     */
    protected function removeNonUpdatePart(array $productPart)
    {
        if (isset($productPart[1]['url_key'])) {
            unset($productPart[1]['url_key']);
        }

        return $productPart;
    }
}
