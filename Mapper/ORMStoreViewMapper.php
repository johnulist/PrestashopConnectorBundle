<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Mapper;

use Pim\Bundle\PrestashopConnectorBundle\Manager\SimpleMappingManager;
use Pim\Bundle\PrestashopConnectorBundle\Manager\LocaleManager;
use Pim\Bundle\PrestashopConnectorBundle\Validator\Constraints\HasValidCredentialsValidator;

/**
 * Prestashop storeview mapper.
 *
 */
class ORMStoreViewMapper extends ORMPimMapper
{
    /** @var LocaleManager */
    protected $localeManager;

    /**
     * @param HasValidCredentialsValidator $hasValidCredentialsValidator
     * @param SimpleMappingManager         $simpleMappingManager
     * @param string                       $rootIdentifier
     * @param LocaleManager                $localeManager
     */
    public function __construct(
        HasValidCredentialsValidator $hasValidCredentialsValidator,
        SimpleMappingManager $simpleMappingManager,
        $rootIdentifier,
        LocaleManager $localeManager
    ) {
        parent::__construct($hasValidCredentialsValidator, $simpleMappingManager, $rootIdentifier);

        $this->localeManager = $localeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllSources()
    {
        $sources = [];

        if ($this->isValid()) {
            $codes = $this->localeManager->getActiveCodes();

            foreach ($codes as $code) {
                $sources[] = ['id' => $code, 'text' => $code];
            }
        }

        return $sources;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllTargets()
    {
        return [];
    }
}
