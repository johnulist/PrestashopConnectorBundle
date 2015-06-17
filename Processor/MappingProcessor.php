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
 * @author    Antoine Guigan <antoine@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
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
