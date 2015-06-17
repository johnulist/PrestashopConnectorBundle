<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Is the given currency valid ?
 *
 * @Annotation
 */
class HasValidCurrency extends Constraint
{
    /** @var string */
    public $message = 'The given currency is not valid (check that the selected currency is in channel\'s currencies)';

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
        return 'has_valid_currency';
    }
}
