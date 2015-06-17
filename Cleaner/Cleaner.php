<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Cleaner;

use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Pim\Bundle\PrestashopConnectorBundle\Item\PrestashopItemStep;

/**
 * Prestashop cleaner item step.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
abstract class Cleaner extends PrestashopItemStep implements StepExecutionAwareInterface
{
    /** @staticvar string */
    const DO_NOTHING = 'do_nothing';

    /** staticvar string */
    const DISABLE    = 'disable';

    /** staticvar string */
    const DELETE     = 'delete';

    /** @var StepExecution */
    protected $stepExecution;

    /** @var string */
    protected $notInPimAnymoreAction;

    /**
     * @return string
     */
    public function getNotInPimAnymoreAction()
    {
        return $this->notInPimAnymoreAction;
    }

    /**
     * @param string $notInPimAnymoreAction
     *
     * @return Cleaner
     */
    public function setNotInPimAnymoreAction($notInPimAnymoreAction)
    {
        $this->notInPimAnymoreAction = $notInPimAnymoreAction;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function execute();

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return array_merge(
            parent::getConfigurationFields(),
            [
                'notInPimAnymoreAction' => [
                    'type'    => 'choice',
                    'options' => [
                        'choices'  => [
                            Cleaner::DO_NOTHING => 'pim_prestashop_connector.export.do_nothing.label',
                            Cleaner::DISABLE    => 'pim_prestashop_connector.export.disable.label',
                            Cleaner::DELETE     => 'pim_prestashop_connector.export.delete.label',
                        ],
                        'required' => true,
                        'help'     => 'pim_prestashop_connector.export.notInPimAnymoreAction.help',
                        'label'    => 'pim_prestashop_connector.export.notInPimAnymoreAction.label',
                        'attr'     => ['class' => 'select2'],
                    ],
                ],
            ]
        );
    }
}
