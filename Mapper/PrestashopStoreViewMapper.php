<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Mapper;

use Pim\Bundle\PrestashopConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\PrestashopConnectorBundle\Validator\Constraints\HasValidCredentialsValidator;

/**
 * Prestashop storeview mapper.
 *
 */
class PrestashopStoreViewMapper extends PrestashopMapper
{
    /** @var WebserviceGuesser */
    protected $webserviceGuesser;

    /**
     * @param HasValidCredentialsValidator $hasValidCredentialsValidator
     * @param WebserviceGuesser            $webserviceGuesser
     */
    public function __construct(
        HasValidCredentialsValidator $hasValidCredentialsValidator,
        WebserviceGuesser $webserviceGuesser
    ) {
        parent::__construct($hasValidCredentialsValidator);

        $this->webserviceGuesser = $webserviceGuesser;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllTargets()
    {
        $targets = [];

        if ($this->isValid()) {
            $storeViews = $this->webserviceGuesser->getWebservice($this->clientParameters)->getStoreViewsList();

            foreach ($storeViews as $storeView) {
                if ($storeView['code'] !== $this->defaultStoreView) {
                    $targets[] = ['id' => $storeView['code'], 'text' => $storeView['code']];
                }
            }
        }

        return $targets;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier($rootIdentifier = 'storeview')
    {
        return parent::getIdentifier($rootIdentifier);
    }
}
