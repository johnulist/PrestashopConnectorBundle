<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Is the given default locale valid ?
 *
 * @Annotation
 */
class HasValidDefaultLocale extends Constraint
{
    /** @var string */
    public $message = 'The given default locale is not valid (check that the selected locale is in channel\'s locales)';

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
        return 'has_valid_default_locale';
    }
}
