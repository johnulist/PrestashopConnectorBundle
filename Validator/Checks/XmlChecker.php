<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Validator\Checks;

use Pim\Bundle\PrestashopConnectorBundle\Validator\Exception\InvalidXmlException;

/**
 * Tool for check your xml.
 *
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
