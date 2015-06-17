<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Is the given credentials valid ?
 *
 * @Annotation
 */
class HasValidCredentials extends Constraint
{
    /** @var string */
    public $messageUrlNotReachable   = 'pim_prestashop_connector.export.validator.url_not_reachable';

    /** @var string */
    public $messageSoapNotValid      = 'pim_prestashop_connector.export.validator.soap_url_not_valid';

    /** @var string */
    public $messageXmlNotValid       = 'pim_prestashop_connector.export.validator.xml_not_valid';

    /** @var string */
    public $messageUsername          = 'pim_prestashop_connector.export.validator.authentication_failed';

    /**
     *{@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     *{@inheritDoc}
     */
    public function validatedBy()
    {
        return 'has_valid_prestashop_credentials';
    }
}
