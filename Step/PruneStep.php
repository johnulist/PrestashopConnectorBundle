<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Step;

use Akeneo\Bundle\BatchBundle\Step\AbstractStep;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\AbstractConfigurableStepElement;
use Pim\Bundle\PrestashopConnectorBundle\Cleaner\Cleaner;
use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;

/**
 * A step to delete element that are no longer in PIM or in the channel.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class PruneStep extends AbstractStep
{
    /** @var Cleaner */
    protected $cleaner;

    /**
     * {@inheritdoc}
     */
    protected function doExecute(StepExecution $stepExecution)
    {
        // inject the step execution in the step item to be able to log summary info during execution
        $this->cleaner->setStepExecution($stepExecution);

        try {
            $this->cleaner->execute();
        } catch (InvalidItemException $e) {
            $this->handleStepExecutionWarning($stepExecution, $this->cleaner, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        $configuration = [];
        foreach ($this->getConfigurableStepElements() as $stepElement) {
            if ($stepElement instanceof AbstractConfigurableStepElement) {
                foreach ($stepElement->getConfiguration() as $key => $value) {
                    if (!isset($configuration[$key]) || $value) {
                        $configuration[$key] = $value;
                    }
                }
            }
        }

        return $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfiguration(array $config)
    {
        foreach ($this->getConfigurableStepElements() as $stepElement) {
            if ($stepElement instanceof AbstractConfigurableStepElement) {
                $stepElement->setConfiguration($config);
            }
        }

        $this->afterConfigurationSet();
    }

    /**
     * Called after configuration affectation.
     */
    protected function afterConfigurationSet()
    {
    }

    /**
     * @return Cleaner
     */
    public function getCleaner()
    {
        return $this->cleaner;
    }

    /**
     * @param Cleaner $cleaner
     */
    public function setCleaner(Cleaner $cleaner)
    {
        $this->cleaner = $cleaner;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurableStepElements()
    {
        return ['cleaner' => $this->getCleaner()];
    }

    /**
     * Handle step execution warning.
     *
     * @param StepExecution                   $stepExecution
     * @param AbstractConfigurableStepElement $element
     * @param InvalidItemException            $e
     */
    protected function handleStepExecutionWarning(
        StepExecution $stepExecution,
        AbstractConfigurableStepElement $element,
        InvalidItemException $e
    ) {
        if ($element instanceof AbstractConfigurableStepElement) {
            $warningName = $element->getName();
        } else {
            $warningName = get_class($element);
        }

        $stepExecution->addWarning($warningName, $e->getMessage(), $e->getMessageParameters(), $e->getItem());
        $this->dispatchInvalidItemEvent(
            get_class($element),
            $e->getMessage(),
            $e->getMessageParameters(),
            $e->getItem()
        );
    }
}
