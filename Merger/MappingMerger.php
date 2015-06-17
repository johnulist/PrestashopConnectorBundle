<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Merger;

use Pim\Bundle\PrestashopConnectorBundle\Mapper\MappingCollection;

/**
 * Mapping merger.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MappingMerger
{
    /** @var array */
    protected $mappers = [];

    /** @var string */
    protected $name;

    /** @var string */
    protected $direction;

    /** @var boolean */
    protected $hasParametersSet = false;

    /** @var boolean */
    protected $allowAddition = true;

    /**
     * @param \Pim\Bundle\PrestashopConnectorBundle\Mapper\MapperInterface[] $mappers
     * @param string                                                      $name
     * @param string                                                      $direction
     * @param boolean                                                     $allowAddition
     */
    public function __construct(array $mappers, $name, $direction, $allowAddition)
    {
        $this->name          = $name;
        $this->direction     = $direction;
        $this->allowAddition = $allowAddition;

        foreach ($mappers as $mapper) {
            if (!isset($this->mappers[$mapper->getPriority()])) {
                $this->mappers[$mapper->getPriority()] = [];
            }

            $this->mappers[$mapper->getPriority()][] = $mapper;
        }

        ksort($this->mappers);

        $this->hasParametersSet = true;
    }

    /**
     * Get mapping for all mappers.
     *
     * @return array
     */
    public function getMapping()
    {
        $mergedMapping = new MappingCollection();

        if ($this->hasParametersSet) {
            foreach ($this->getOrderedMappers() as $mapper) {
                $mergedMapping->merge($mapper->getMapping());
            }
        }

        return $mergedMapping;
    }

    /**
     * Set mapping for all mappers.
     *
     * @param array $mapping
     */
    public function setMapping(array $mapping)
    {
        if ($this->hasParametersSet) {
            foreach ($this->getOrderedMappers() as $mapper) {
                $mapper->setMapping($mapping);
            }
        }
    }

    /**
     * Get configuration field for the merger.
     *
     * @return array
     */
    public function getConfigurationField()
    {
        return [
            $this->name.'Mapping' => [
                'type'    => 'textarea',
                'options' => [
                    'required' => false,
                    'attr'     => [
                        'class' => 'mapping-field',
                        'data-sources' => json_encode($this->getAllSources()),
                        'data-targets' => json_encode($this->getAllTargets()),
                        'data-name'    => $this->name,
                    ],
                    'label' => 'pim_connector_mapping.'.$this->direction.'.'.$this->name.'Mapping.label',
                    'help'  => 'pim_connector_mapping.'.$this->direction.'.'.$this->name.'Mapping.help',
                ],
            ],
        ];
    }

    /**
     * Get all sources (for suggestion).
     *
     * @return array
     */
    protected function getAllSources()
    {
        $sources = [];
        foreach ($this->getOrderedMappers() as $mapper) {
            $sources = array_merge($sources, $mapper->getAllSources());
        }

        return ['sources' => $sources];
    }

    /**
     * Get all targets (for suggestion).
     *
     * @return array
     */
    protected function getAllTargets()
    {
        $targets = [];

        if ($this->hasParametersSet) {
            foreach ($this->getOrderedMappers() as $mapper) {
                $targets = array_merge($targets, $mapper->getAllTargets());
            }
        }

        return ['targets' => $targets, 'allowAddition' => $this->allowAddition];
    }

    /**
     * Get mappers ordered by priority.
     *
     * @return \Pim\Bundle\PrestashopConnectorBundle\Mapper\MapperInterface[]
     */
    protected function getOrderedMappers()
    {
        $orderedMappers = [];

        foreach ($this->mappers as $mappers) {
            foreach ($mappers as $mapper) {
                $orderedMappers[] = $mapper;
            }
        }

        return $orderedMappers;
    }
}
