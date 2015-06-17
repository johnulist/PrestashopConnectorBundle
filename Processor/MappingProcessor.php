<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Processor;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Pim\Bundle\PrestashopConnectorBundle\Entity\SimpleMapping;
use Akeneo\Bundle\BatchBundle\Item\AbstractConfigurableStepElement;
use Akeneo\Bundle\BatchBundle\Item\ItemProcessorInterface;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

/**
 * Mapping processor for mapping fixtures.
 *
 */
class MappingProcessor extends AbstractConfigurableStepElement implements
    ItemProcessorInterface,
    StepExecutionAwareInterface
{
    /** @var StepExecution */
    protected $stepExecution;

    /**
     * {@inheritdoc}
     */
    public function process($item)
    {
        $simpleMapping = new SimpleMapping();
        $simpleMapping->setIdentifier(sha1($item['identifier']));
        $simpleMapping->setSource($item['source']);
        $simpleMapping->setTarget($item['target']);

        return $simpleMapping;
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }

    /**
     * @return array
     */
    public function getConfigurationFields()
    {
        return [];
    }
}
