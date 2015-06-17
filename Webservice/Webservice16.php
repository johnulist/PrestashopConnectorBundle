<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Webservice;

/**
 * A prestashop soap client to abstract interaction with the prestashop api (version 1.6 and above).
 *
 */
class Webservice16 extends Webservice
{
    /**
     * Get prestashop storeview list from prestashop.
     *
     * @return array
     */
    public function getStoreViewsList()
    {
        return [];
    }
}
