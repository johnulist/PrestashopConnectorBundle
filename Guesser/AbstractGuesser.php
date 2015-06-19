<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Guesser;

use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopSoapClient;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\RestCallException;

/**
 * A prestashop guesser abstract class.
 *
 */
abstract class AbstractGuesser
{
    /** @staticvar string */
    const PRESTASHOP_VERSION_1_14 = '1.14';

    /** @staticvar string */
    const PRESTASHOP_VERSION_1_13 = '1.13';

    /** @staticvar string */
    const PRESTASHOP_VERSION_1_12 = '1.12';

    /** @staticvar string */
    const PRESTASHOP_VERSION_1_11 = '1.11';

    /** @staticvar string */
    const PRESTASHOP_VERSION_1_9  = '1.9';

    /** @staticvar string */
    const PRESTASHOP_VERSION_1_8  = '1.8';

    /** @staticvar string */
    const PRESTASHOP_VERSION_1_7  = '1.7';

    /** @staticvar string */
    const PRESTASHOP_VERSION_1_6  = '1.6';

    /** @staticvar string */
    const PRESTASHOP_CORE_ACCESS_DENIED = 'Access denied.';

    /** @staticvar string */
    const UNKNOWN_VERSION = 'unknown_version';

    /** @staticvar string */
    const PRESTASHOP_VERSION_NOT_SUPPORTED_MESSAGE = 'Your Prestashop version is not supported yet.';

    /** @var string */
    protected $version = null;

    /**
     * Get the Prestashop version for the given client.
     *
     * @param PrestashopSoapClient $client
     *
     * @return float
     */
    protected function getPrestashopVersion(PrestashopSoapClient $client = null)
    {
        if (null === $client) {
            return null;
        }

        if (!$this->version) {
            try {
                $prestashopVersion = $client->call('core_prestashop.info')['prestashop_version'];
            } catch (\SoapFault $e) {
                return self::PRESTASHOP_VERSION_1_6;
            } catch (RestCallException $e) {
                throw $e;
            }

            $pattern = '/^(?P<version>[0-9]+\.[0-9]+)(\.[0-9])*/';

            if (preg_match($pattern, $prestashopVersion, $matches)) {
                $this->version = $matches['version'];
            } else {
                $this->version = $prestashopVersion;
            }
        }

        return $this->version;
    }
}
