<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Mapper;

use Pim\Bundle\PrestashopConnectorBundle\Manager\SimpleMappingManager;

/**
 * ORM mapper.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ORMMapper extends Mapper
{
    /** @var SimpleMappingManager */
    protected $simpleMappingManager;

    /** @var string */
    protected $rootIdentifier;

    /**
     * @param SimpleMappingManager $simpleMappingManager
     * @param string               $rootIdentifier
     */
    public function __construct(
        SimpleMappingManager $simpleMappingManager,
        $rootIdentifier
    ) {
        $this->simpleMappingManager = $simpleMappingManager;
        $this->rootIdentifier       = $rootIdentifier;
    }

    /**
     * {@inheritdoc}
     */
    public function getMapping()
    {
        $simpleMappingItems = $this->simpleMappingManager->getMapping($this->getIdentifier($this->rootIdentifier));

        $mapping = new MappingCollection();
        foreach ($simpleMappingItems as $simpleMappingItem) {
            $mapping->add(
                array(
                    'source'    => $simpleMappingItem->getSource(),
                    'target'    => $simpleMappingItem->getTarget(),
                    'deletable' => true,
                )
            );
        }

        return $mapping;
    }

    /**
     * {@inheritdoc}
     */
    public function setMapping(array $mapping)
    {
        $this->simpleMappingManager->setMapping($mapping, $this->getIdentifier($this->rootIdentifier));
    }

    /**
     * {@inheritdoc}
     */
    public function getAllTargets()
    {
        $targets = array();

        if ($this->isValid()) {
            $elements = $this->simpleMappingManager->getMapping($this->getIdentifier($this->rootIdentifier));

            foreach ($elements as $element) {
                $targets[] = array('id' => $element->getTarget(), 'text' => $element->getTarget());
            }
        }

        return $targets;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllSources()
    {
        $sources = array();

        if ($this->isValid()) {
            $elements = $this->simpleMappingManager->getMapping($this->getIdentifier($this->rootIdentifier));

            foreach ($elements as $element) {
                $sources[] = array('id' => $element->getSource(), 'text' => $element->getSource());
            }
        }

        return $sources;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 10;
    }
}
