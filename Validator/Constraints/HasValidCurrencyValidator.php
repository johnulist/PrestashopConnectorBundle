<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\PrestashopConnectorBundle\Processor\AbstractProductProcessor;

/**
 * Validator for currency.
 *
 */
class HasValidCurrencyValidator extends ConstraintValidator
{
    /** @var ChannelManager */
    protected $channelManager;

    /** @param ChannelManager $channelManager */
    public function __construct(ChannelManager $channelManager)
    {
        $this->channelManager = $channelManager;
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param AbstractProductProcessor $value      The value that should be validated
     * @param Constraint               $constraint The constraint for the validation
     *
     * @api
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof AbstractProductProcessor) {
            return null;
        }

        if ($channel = $this->channelManager->getChannelByCode($value->getChannel())) {
            foreach ($channel->getCurrencies() as $currency) {
                if ($currency->getCode() === $value->getCurrency()) {
                    return null;
                }
            }
        }

        $this->context->addViolationAt('currency', $constraint->message, ['currency']);
    }
}
