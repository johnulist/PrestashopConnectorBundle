<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Webservice;

use Pim\Bundle\CatalogBundle\Model\ProductInterface;

/**
 * A prestashop soap client to abstract interaction with the prestashop api.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Webservice
{
    /** @staticvar string */
    const SOAP_ACTION_CATALOG_PRODUCT_CREATE                 = 'catalog_product.create';

    /** @staticvar string */
    const SOAP_ACTION_CATALOG_PRODUCT_UPDATE                 = 'catalog_product.update';

    /** @staticvar string */
    const SOAP_ACTION_CATALOG_PRODUCT_DELETE                 = 'catalog_product.delete';

    /** @staticvar string */
    const SOAP_ACTION_CATALOG_PRODUCT_CURRENT_STORE          = 'catalog_product.currentStore';

    /** @staticvar string */
    const SOAP_ACTION_CATALOG_PRODUCT_LIST                   = 'catalog_product.list';

    /** @staticvar string */
    const SOAP_ACTION_PRODUCT_ATTRIBUTE_SET_LIST             = 'product_attribute_set.list';

    /** @staticvar string */
    const SOAP_ACTION_PRODUCT_ATTRIBUTE_SET_ATTRIBUTE_ADD    = 'product_attribute_set.attributeAdd';

    /** @staticvar string */
    const SOAP_ACTION_PRODUCT_ATTRIBUTE_SET_ATTRIBUTE_REMOVE = 'product_attribute_set.attributeRemove';

    /** @staticvar string */
    const SOAP_ACTION_PRODUCT_ATTRIBUTE_SET_CREATE           = 'product_attribute_set.create';

    /** @staticvar string */
    const SOAP_ACTION_PRODUCT_ATTRIBUTE_SET_GROUP_ADD        = 'product_attribute_set.groupAdd';

    /** @staticvar string */
    const SOAP_ACTION_PRODUCT_ATTRIBUTE_SET_GROUP_REMOVE     = 'product_attribute_set.groupRemove';

    /** @staticvar string */
    const SOAP_ACTION_PRODUCT_ATTRIBUTE_SET_GROUP_RENAME     = 'product_attribute_set.groupRename';

    /** @staticvar string */
    const SOAP_ACTION_PRODUCT_ATTRIBUTE_SET_REMOVE           = 'product_attribute_set.remove';

    /** @staticvar string */
    const SOAP_ACTION_PRODUCT_ATTRIBUTE_LIST                 = 'catalog_product_attribute.list';

    /** @staticvar string */
    const SOAP_ACTION_ATTRIBUTE_OPTION_LIST                  = 'catalog_product_attribute.options';

    /** @staticvar string */
    const SOAP_ACTION_ATTRIBUTE_OPTION_ADD                   = 'catalog_product_attribute.addOption';

    /** @staticvar string */
    const SOAP_ACTION_ATTRIBUTE_OPTION_REMOVE                = 'catalog_product_attribute.removeOption';

    /** @staticvar string */
    const SOAP_ACTION_ATTRIBUTE_CREATE                       = 'product_attribute.create';

    /** @staticvar string */
    const SOAP_ACTION_ATTRIBUTE_UPDATE                       = 'product_attribute.update';

    /** @staticvar string */
    const SOAP_ACTION_ATTRIBUTE_REMOVE                       = 'product_attribute.remove';

    /** @staticvar string */
    const SOAP_ACTION_STORE_LIST                             = 'store.list';

    /** @staticvar string */
    const SOAP_ACTION_PRODUCT_MEDIA_CREATE                   = 'catalog_product_attribute_media.create';

    /** @staticvar string */
    const SOAP_ACTION_PRODUCT_MEDIA_LIST                     = 'catalog_product_attribute_media.list';

    /** @staticvar string */
    const SOAP_ACTION_PRODUCT_MEDIA_REMOVE                   = 'catalog_product_attribute_media.remove';

    /** @staticvar string */
    const SOAP_ACTION_CATEGORY_TREE                          = 'catalog_category.tree';

    /** @staticvar string */
    const SOAP_ACTION_CATEGORY_CREATE                        = 'catalog_category.create';

    /** @staticvar string */
    const SOAP_ACTION_CATEGORY_UPDATE                        = 'catalog_category.update';

    /** @staticvar string */
    const SOAP_ACTION_CATEGORY_DELETE                        = 'catalog_category.delete';

    /** @staticvar string */
    const SOAP_ACTION_CATEGORY_MOVE                          = 'catalog_category.move';

    /** @staticvar string */
    const SOAP_ACTION_LINK_LIST                              = 'catalog_product_link.list';

    /** @staticvar string */
    const SOAP_ACTION_LINK_REMOVE                            = 'catalog_product_link.remove';

    /** @staticvar string */
    const SOAP_ACTION_LINK_CREATE                            = 'catalog_product_link.assign';

    /** @staticvar string */
    const SOAP_DEFAULT_STORE_VIEW                            = 'default';

    /** @staticvar string */
    const IMAGES                                             = 'images';

    /** @staticvar string */
    const SOAP_ATTRIBUTE_ID                                  = 'attribute_id';

    /** @staticvar string */
    const SMALL_IMAGE                                        = 'small_image';

    /** @staticvar string */
    const BASE_IMAGE                                         = 'image';

    /** @staticvar string */
    const THUMBNAIL                                          = 'thumbnail';

    /** @staticvar string */
    const SELECT                                             = 'select';

    /** @staticvar string */
    const MULTI_SELECT                                       = 'multiselect';

    /** @staticvar int */
    const MAXIMUM_CALLS                                      = 1;

    /** @staticvar int */
    const CREATE_PRODUCT_SIZE                                = 5;

    /** @staticvar int */
    const CREATE_CONFIGURABLE_SIZE                           = 4;

    /** @staticvar string */
    const CONFIGURABLE_IDENTIFIER_PATTERN                    = 'conf-%s';

    /** @staticvar int */
    const MAGENTO_STATUS_DISABLE                             = 2;

    /** @staticvar int */
    const MAGENTO_PRODUCT_UPDATE_USELESS                     = 2;

    /** @staticvar int */
    const ADMIN_STOREVIEW                                    = 0;

    /** @var PrestashopSoapClient */
    protected $client;

    /** @var array */
    protected $prestashopAttributeSets;

    /** @var array */
    protected $prestashopStoreViewList;

    /** @var array */
    protected $prestashopAttributes = [];

    /** @var array */
    protected $attributeList       = [];

    /** @var array */
    protected $attributes          = [];

    /** @var array */
    protected $attributeSetList    = [];

    /** @var array */
    protected $attributeOptionList = [];

    /** @var array */
    protected $categories          = [];

    /**
     * @param PrestashopSoapClient $client
     */
    public function __construct(PrestashopSoapClient $client)
    {
        $this->client = $client;
    }

    /**
     * Get attribute options for all attributes.
     *
     * @return array
     */
    public function getAllAttributesOptions()
    {
        $attributeList = $this->getAllAttributes();

        foreach ($attributeList as $attributeCode => $attribute) {
            if (in_array($attribute['type'], [self::SELECT, self::MULTI_SELECT])) {
                if (!isset($this->attributeOptionList[$attributeCode])) {
                    $this->attributeOptionList[$attributeCode] = $this->getAttributeOptions($attributeCode);
                }
            }
        }

        return $this->attributeOptionList;
    }

    /**
     * Get all attributes from prestashop.
     *
     * @return array
     */
    public function getAllAttributes()
    {
        if (!$this->attributeList) {
            $attributeSetList = $this->getAttributeSetList();
            foreach (array_keys($attributeSetList) as $attributeSet) {
                $attributes = $this->getAttributeList($attributeSet);
                $this->attributeSetList[$attributeSet] = [];

                foreach ($attributes as $attribute) {
                    $this->attributeList[$attribute['code']]              = $attribute;
                    $this->attributeSetList[$attributeSet][$attributeSet] = $attribute['code'];
                }
            }
        }

        return $this->attributeList;
    }

    /**
     * Get attribute list for a given attribute set code.
     *
     * @param string $attributeSetCode
     *
     * @return array
     */
    public function getAttributeList($attributeSetCode)
    {
        if (!isset($this->attributes[$attributeSetCode])) {
            $id = $this->getAttributeSetId($attributeSetCode);

            $this->attributes[$attributeSetCode] = $this->client->call(
                self::SOAP_ACTION_PRODUCT_ATTRIBUTE_LIST,
                $id
            );
        }

        return $this->attributes[$attributeSetCode];
    }

    /**
     * Get products status in prestashop (do they exist ?).
     *
     * @param array $products
     *
     * @return array
     */
    public function getProductsStatus(array $products = [])
    {
        $skus = $this->getProductsIds($products);

        return $this->getStatusForSkus($skus);
    }

    /**
     * Get configurables status in prestashop (do they exist ?).
     *
     * @param array $configurables
     *
     * @return array
     */
    public function getConfigurablesStatus(array $configurables = [])
    {
        $skus = $this->getConfigurablesIds($configurables);

        return $this->getStatusForSkus($skus);
    }

    /**
     * Get prestashop attributeSets from the prestashop api.
     *
     * @param string $code
     *
     * @throws AttributeSetNotFoundException If If the attribute doesn't exist on Prestashop side
     *
     * @return mixed
     */
    public function getAttributeSetId($code)
    {
        $this->getAttributeSetList();

        if (isset($this->prestashopAttributeSets[$code])) {
            return $this->prestashopAttributeSets[$code];
        } else {
            throw new AttributeSetNotFoundException(
                'The attribute set for code "'.$code.'" was not found on Prestashop. Please create it before proceed.'
            );
        }
    }

    /**
     * Get prestashop storeview list from prestashop.
     *
     * @return array
     */
    public function getStoreViewsList()
    {
        if (!$this->prestashopStoreViewList) {
            $this->prestashopStoreViewList = $this->client->call(
                self::SOAP_ACTION_STORE_LIST
            );
        }

        return $this->prestashopStoreViewList;
    }

    /**
     * Get all images attached to a product.
     *
     * @param string $sku
     * @param string $defaultLocalStore
     *
     * @return array
     */
    public function getImages($sku, $defaultLocalStore)
    {
        try {
            $images = $this->client->call(
                self::SOAP_ACTION_PRODUCT_MEDIA_LIST,
                [
                    $sku,
                    $defaultLocalStore,
                    'sku',
                ]
            );
        } catch (\Exception $e) {
            $images = [];
        }

        return $images;
    }

    /**
     * Send all product images.
     *
     * @param array $images
     */
    public function sendImages($images)
    {
        foreach ($images as $image) {
            $this->client->addCall(
                [
                    self::SOAP_ACTION_PRODUCT_MEDIA_CREATE,
                    $image,
                ]
            );
        }
    }

    /**
     * Delete image for a given sku and a given filename.
     *
     * @param string $sku
     * @param string $imageFilename
     *
     * @return string
     */
    public function deleteImage($sku, $imageFilename)
    {
        return $this->client->call(
            self::SOAP_ACTION_PRODUCT_MEDIA_REMOVE,
            [
                $sku,
                $imageFilename,
                'sku',
            ]
        );
    }

    /**
     * Add the call to update the given product part.
     *
     * @param array $productPart
     */
    public function updateProductPart($productPart)
    {
        $this->client->addCall(
            [self::SOAP_ACTION_CATALOG_PRODUCT_UPDATE, $productPart]
        );
    }

    /**
     * Add a call for the given product part.
     *
     * @param array $productPart
     */
    public function sendProduct($productPart)
    {
        $storeViewList = $this->getStoreViewsList();

        if (count($productPart) === static::CREATE_PRODUCT_SIZE ||
            count($productPart) === static::CREATE_CONFIGURABLE_SIZE &&
            $productPart[static::CREATE_CONFIGURABLE_SIZE - 1] != 'sku'
        ) {
            $this->client->addCall([static::SOAP_ACTION_CATALOG_PRODUCT_CREATE, $productPart]);
            if (count($storeViewList) > 1 && count($productPart) === static::CREATE_PRODUCT_SIZE) {
                $this->updateProductInMultipleStoreViews($productPart);
            }
        } else {
            $this->client->addCall([static::SOAP_ACTION_CATALOG_PRODUCT_UPDATE, $productPart]);
            if (count($storeViewList) > 1) {
                $this->updateProductInAdminStoreView($productPart);
            }
        }
    }

    /**
     * Get categories status from Prestashop.
     *
     * @return array
     */
    public function getCategoriesStatus()
    {
        if (!$this->categories) {
            $tree = $this->client->call(
                self::SOAP_ACTION_CATEGORY_TREE
            );

            $this->categories = $this->flattenCategoryTree($tree);
        }

        return $this->categories;
    }

    /**
     * Send new category.
     *
     * @param array $category
     *
     * @return int
     */
    public function sendNewCategory(array $category)
    {
        $categoryId =  $this->client->call(
            self::SOAP_ACTION_CATEGORY_CREATE,
            $category
        );

        $storeViewList = $this->getStoreViewsList();

        if (count($storeViewList) > 1) {
            $this->updateCategoryInAdminStoreView($category, $categoryId);
        }

        return $categoryId;
    }

    /**
     * Send update category.
     *
     * @param array $category
     *
     * @return int
     */
    public function sendUpdateCategory(array $category)
    {
        return $this->client->call(
            self::SOAP_ACTION_CATEGORY_UPDATE,
            $category
        );
    }

    /**
     * Send move category.
     *
     * @param array $category
     *
     * @return int
     */
    public function sendMoveCategory(array $category)
    {
        return $this->client->call(
            self::SOAP_ACTION_CATEGORY_MOVE,
            $category
        );
    }

    /**
     * Flatten the category tree from prestashop.
     *
     * @param array $tree
     *
     * @return array
     */
    protected function flattenCategoryTree(array $tree)
    {
        $result = [$tree['category_id'] => $tree];

        foreach ($tree['children'] as $children) {
            $result = $result + $this->flattenCategoryTree($children);
        }

        return $result;
    }

    /**
     * Disable the given category on Prestashop.
     *
     * @param string $categoryId
     *
     * @return int
     */
    public function disableCategory($categoryId)
    {
        return $this->client->call(
            self::SOAP_ACTION_CATEGORY_UPDATE,
            [
                $categoryId,
                [
                    'is_active'         => 0,
                    'available_sort_by' => 1,
                    'default_sort_by'   => 1,
                ],
            ]
        );
    }

    /**
     * Delete the given category on Prestashop.
     *
     * @param string $categoryId
     *
     * @return int
     */
    public function deleteCategory($categoryId)
    {
        return $this->client->call(
            self::SOAP_ACTION_CATEGORY_DELETE,
            [
                $categoryId,
            ]
        );
    }

    /**
     * Get associations status.
     *
     * @param ProductInterface $product
     *
     * @return array
     */
    public function getAssociationsStatus(ProductInterface $product)
    {
        $associationStatus = [];
        $sku               = (string) $product->getIdentifier();

        $associationStatus['up_sell'] = $this->client->call(
            self::SOAP_ACTION_LINK_LIST,
            [
                'up_sell',
                $sku,
                'sku',
            ]
        );

        $associationStatus['cross_sell'] = $this->client->call(
            self::SOAP_ACTION_LINK_LIST,
            [
                'cross_sell',
                $sku,
                'sku',
            ]
        );

        $associationStatus['related'] = $this->client->call(
            self::SOAP_ACTION_LINK_LIST,
            [
                'related',
                $sku,
                'sku',
            ]
        );

        $associationStatus['grouped'] = $this->client->call(
            self::SOAP_ACTION_LINK_LIST,
            [
                'grouped',
                $sku,
                'sku',
            ]
        );

        return $associationStatus;
    }

    /**
     * Delete a product association.
     *
     * @param array $productAssociation
     */
    public function removeProductAssociation(array $productAssociation)
    {
        $this->client->call(
            self::SOAP_ACTION_LINK_REMOVE,
            $productAssociation
        );
    }

    /**
     * Create a product association.
     *
     * @param array $productAssociation
     */
    public function createProductAssociation(array $productAssociation)
    {
        $this->client->call(
            self::SOAP_ACTION_LINK_CREATE,
            $productAssociation
        );
    }

    /**
     * Disable a product.
     *
     * @param string $productSku
     */
    public function disableProduct($productSku)
    {
        $this->client->call(
            self::SOAP_ACTION_CATALOG_PRODUCT_UPDATE,
            [
                $productSku,
                [
                    'status' => self::MAGENTO_STATUS_DISABLE,
                ],
            ]
        );
    }

    /**
     * Delete a product.
     *
     * @param string $productSku
     */
    public function deleteProduct($productSku)
    {
        $this->client->call(
            self::SOAP_ACTION_CATALOG_PRODUCT_DELETE,
            [
                $productSku,
            ]
        );
    }

    /**
     * Create an option.
     *
     * @param array $option
     */
    public function createOption($option)
    {
        $this->client->call(
            self::SOAP_ACTION_ATTRIBUTE_OPTION_ADD,
            $option
        );
    }

    /**
     * Create an attribute.
     *
     * @param array $attribute
     *
     * @return integer ID of the created attribute
     */
    public function createAttribute($attribute)
    {
        $result = $this->client->call(
            self::SOAP_ACTION_ATTRIBUTE_CREATE,
            [$attribute]
        );

        return $result;
    }

    /**
     * Update an attribute.
     *
     * @param array $attribute
     *
     * @return boolean
     */
    public function updateAttribute($attribute)
    {
        $result = $this->client->call(
            self::SOAP_ACTION_ATTRIBUTE_UPDATE,
            $attribute
        );

        return $result;
    }

    /**
     * Delete an attribute.
     *
     * @param string $attributeCode
     */
    public function deleteAttribute($attributeCode)
    {
        $this->client->call(
            self::SOAP_ACTION_ATTRIBUTE_REMOVE,
            $attributeCode
        );
    }

    /**
     * Get options for the given attribute.
     *
     * @param string $attributeCode Attribute code
     *
     * @return array the formated options for the given attribute
     */
    public function getAttributeOptions($attributeCode)
    {
        $options = $this->client->call(
            self::SOAP_ACTION_ATTRIBUTE_OPTION_LIST,
            [$attributeCode, self::ADMIN_STOREVIEW]
        );

        $formatedOptions = [];

        foreach ($options as $option) {
            $formatedOptions[$option['label']] = $option['value'];
        }

        return $formatedOptions;
    }

    /**
     * Delete an option.
     *
     * @param string $optionId
     * @param string $attributeCode
     */
    public function deleteOption($optionId, $attributeCode)
    {
        $this->client->call(
            self::SOAP_ACTION_ATTRIBUTE_OPTION_REMOVE,
            [
                $attributeCode,
                $optionId,
            ]
        );
    }

    /**
     * Get the prestashop attributeSet list from the prestashop platform.
     *
     * @return array Array of attribute sets
     */
    public function getAttributeSetList()
    {
        // On first call we get the prestashop attribute set list
        // (to bind them with our product's families)
        if (!$this->prestashopAttributeSets) {
            $attributeSets = $this->client->call(
                self::SOAP_ACTION_PRODUCT_ATTRIBUTE_SET_LIST
            );

            $this->prestashopAttributeSets = [];

            foreach ($attributeSets as $attributeSet) {
                $this->prestashopAttributeSets[$attributeSet['name']] = $attributeSet['set_id'];
            }
        }

        return $this->prestashopAttributeSets;
    }

    /**
     *  Add an attribute to the attribute set.
     *
     * @param integer $attributeId      Attribute ID
     * @param integer $setId            Attribute set ID
     * @param integer $attributeGroupId Group ID (optional)
     * @param boolean $sortOrder        Sort order (optional)
     *
     * @return boolean True if the attribute is added to an attribute set
     */
    public function addAttributeToAttributeSet(
        $attributeId,
        $setId,
        $attributeGroupId = null,
        $sortOrder = false
    ) {
        return $this->client->call(
            self::SOAP_ACTION_PRODUCT_ATTRIBUTE_SET_ATTRIBUTE_ADD,
            [
                $attributeId,
                $setId,
                $attributeGroupId,
                $sortOrder,
            ]
        );
    }

    /**
     *  Allows you to remove an existing attribute from an attribute set.
     *
     * @param integer $attributeId
     * @param integer $setId
     *
     * @return boolean True if the attribute is removed from an attribute set
     */
    public function removeAttributeFromAttributeSet(
        $attributeId,
        $setId
    ) {
        return $this->client->call(
            self::SOAP_ACTION_PRODUCT_ATTRIBUTE_SET_ATTRIBUTE_REMOVE,
            [
                $attributeId,
                $setId,
            ]
        );
    }

    /**
     *  Allows you to create a new attribute set based on another attribute set.
     *
     * @param integer $attributeSetName Attribute set name
     * @param integer $skeletonSetId    Attribute set ID basing on which the new attribute set will be created
     *
     * @return integer ID of the created attribute set
     */
    public function createAttributeSet(
        $attributeSetName,
        $skeletonSetId = 4
    ) {
        return $this->client->call(
            self::SOAP_ACTION_PRODUCT_ATTRIBUTE_SET_CREATE,
            [
                $attributeSetName,
                $skeletonSetId,
            ]
        );
    }

    /**
     *  Allows you to add a new group for attributes to the attribute set.
     *
     * @param integer $attributeSetId
     * @param string  $groupName
     *
     * @return integer ID of the created group
     */
    public function addAttributeGroupToAttributeSet(
        $attributeSetId,
        $groupName
    ) {
        return $this->client->call(
            self::SOAP_ACTION_PRODUCT_ATTRIBUTE_SET_GROUP_ADD,
            [
                $attributeSetId,
                $groupName,
            ]
        );
    }

    /**
     *  Allows you to remove a group from an attribute set.
     *
     * @param integer $attributeGroupId Group ID
     *
     * @return boolean true (1) if the group is removed
     */
    public function removeAttributeGroupFromAttributeSet(
        $attributeGroupId
    ) {
        return $this->client->call(
            self::SOAP_ACTION_PRODUCT_ATTRIBUTE_SET_GROUP_REMOVE,
            [
                $attributeGroupId,
            ]
        );
    }

    /**
     *  Allows you to rename a group in the attribute set.
     *
     * @param integer $attributeGroupId Group ID
     * @param string  $groupName        New name for the group
     *
     * @return boolean True (1) if the group is renamed
     */
    public function renameAttributeGroupInAttributeSet(
        $attributeGroupId,
        $groupName
    ) {
        return $this->client->call(
            self::SOAP_ACTION_PRODUCT_ATTRIBUTE_SET_GROUP_REMOVE,
            [
                $attributeGroupId,
                $groupName,
            ]
        );
    }

    /**
     *  Allows you to remove an existing attribute set.
     *
     * @param integer $attributeSetId
     * @param boolean $forceProductsRemove
     *
     * @return boolean True (1) if the attribute set is removed
     */
    public function removeAttributeSet(
        $attributeSetId,
        $forceProductsRemove = false
    ) {
        return $this->client->call(
            self::SOAP_ACTION_PRODUCT_ATTRIBUTE_SET_REMOVE,
            [
                $attributeSetId,
                $forceProductsRemove,
            ]
        );
    }

    /**
     * Get the products status for the given skus.
     *
     * @param array $skus
     *
     * @return array
     */
    protected function getStatusForSkus($skus)
    {
        if ($skus) {
            $filters = json_decode(
                json_encode(
                    [
                        'complex_filter' => [
                            [
                                'key' => 'sku',
                                'value' => ['key' => 'in', 'value' => $skus],
                            ],
                        ],
                    ]
                ),
                false
            );
        } else {
            $filters = [];
        }

        return $this->client->call(
            self::SOAP_ACTION_CATALOG_PRODUCT_LIST,
            $filters
        );
    }

    /**
     * Serialize products id in csv.
     *
     * @param array $products The given products
     *
     * @return string The serialization result
     */
    protected function getProductsIds(array $products = [])
    {
        $ids = [];

        foreach ($products as $product) {
            $ids[] = $product->getIdentifier();
        }

        return implode(',', $ids);
    }

    /**
     * Serialize configurables id in csv.
     *
     * @param array $configurables The given configurables
     *
     * @return string The serialization result
     */
    protected function getConfigurablesIds(array $configurables = [])
    {
        $ids = [];

        foreach ($configurables as $configurable) {
            $ids[] = sprintf(
                Webservice::CONFIGURABLE_IDENTIFIER_PATTERN,
                $configurable['group']->getCode()
            );
        }

        return implode(',', $ids);
    }

    /**
     * Update product if there is multiple Prestashop store views.
     *
     * @param array $productPart
     */
    protected function updateProductInMultipleStoreViews(array $productPart)
    {
        $productPartToUpdate = array_merge(
            array_slice($productPart, static::MAGENTO_PRODUCT_UPDATE_USELESS),
            ['sku']
        );
        $this->updateProductPart($productPartToUpdate);

        $this->updateProductInAdminStoreView($productPartToUpdate);
    }

    /**
     * Update product in admin store view.
     *
     * @param array $productPart
     */
    protected function updateProductInAdminStoreView(array $productPart)
    {
        $productPart[2] = static::ADMIN_STOREVIEW;
        $this->updateProductPart($productPart);
    }

    /**
     * @param array  $category
     * @param string $categoryId
     */
    protected function updateCategoryInAdminStoreView($category, $categoryId)
    {
        $category[0] = $categoryId;
        $this->client->addCall([static::SOAP_ACTION_CATEGORY_UPDATE, $category]);

        $category[2] = static::ADMIN_STOREVIEW;
        $this->client->addCall([static::SOAP_ACTION_CATEGORY_UPDATE, $category]);
    }
}
