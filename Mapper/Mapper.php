<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Mapper;

/**
 * Mapper.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Mapper implements MapperInterface
{
    /** @staticvar string */
    const IDENTIFIER_FORMAT = '%s-%s';

    /**
     * {@inheritdoc}
     */
    public function getMapping()
    {
        return new MappingCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function setMapping(array $mapping)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getAllTargets()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAllSources()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier($rootIdentifier = 'generic')
    {
        if ($this->isValid()) {
            return sha1(sprintf(self::IDENTIFIER_FORMAT, $rootIdentifier, ''));
        } else {
            return '';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isValid()
    {
        return true;
    }
}
