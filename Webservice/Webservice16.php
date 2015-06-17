<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Webservice;

/**
 * A prestashop soap client to abstract interaction with the prestashop api (version 1.6 and above).
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
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
