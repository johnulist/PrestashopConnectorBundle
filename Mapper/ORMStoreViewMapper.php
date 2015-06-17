<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Mapper;

use Pim\Bundle\PrestashopConnectorBundle\Manager\SimpleMappingManager;
use Pim\Bundle\PrestashopConnectorBundle\Manager\LocaleManager;
use Pim\Bundle\PrestashopConnectorBundle\Validator\Constraints\HasValidCredentialsValidator;

/**
 * Prestashop storeview mapper.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
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
