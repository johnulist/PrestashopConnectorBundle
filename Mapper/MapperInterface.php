<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Mapper;

/**
 * Defines the interface of a mapper.
 *
 */
interface MapperInterface
{
    /**
     * Get mapper identifier.
     *
     * @param string $rootIdentifier
     *
     * @return string
     */
    public function getIdentifier($rootIdentifier);

    /**
     * @return array
     */
    public function getMapping();

    /**
     * @param array $mapping
     */
    public function setMapping(array $mapping);

    /**
     * @return array
     */
    public function getAllTargets();

    /**
     * @return array
     */
    public function getAllSources();

    /**
     * @return integer
     */
    public function getPriority();

    /**
     * Is the mapper valid ?
     *
     * @return boolean
     */
    public function isValid();
}
