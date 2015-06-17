<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Validator\Checks;

use Pim\Bundle\PrestashopConnectorBundle\Validator\Exception\InvalidXmlException;

/**
 * Tool for check your xml.
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class XmlChecker
{
    /**
     * Check the given xml.
     *
     * @param string $xml
     *
     * @throws InvalidXmlException
     */
    public function checkXml($xml)
    {
        $output = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOERROR);

        if (false === $output) {
            throw new InvalidXMLException();
        }
    }
}
