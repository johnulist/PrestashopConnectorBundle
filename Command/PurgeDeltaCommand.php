<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Purge delta tables for a given job instance code.
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class PurgeDeltaCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('prestashop-connector:delta:purge')
            ->setDescription('Purges delta table from database for a given job instance code.')
            ->addArgument(
                'job_instance_code',
                InputArgument::REQUIRED,
                'From which job instance want you remove ?'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $deltaTablePurger = $this->getDeltaTablePurger();
        $jobInstanceCode  = $input->getArgument('job_instance_code');

        $output->writeln(sprintf('<info>Executing command for "%s" job instance.<info>', $jobInstanceCode));

        try {
            $deltaTablePurger->purge($jobInstanceCode);
            $output->writeln(
                sprintf('<info>Delta related to "%s" job instance has been purged.<info>', $jobInstanceCode)
            );
        } catch (\Exception $e) {
            $output->writeln(
                sprintf('<error>Error appears for "%s" job code : "%s"<error>', $jobInstanceCode, $e->getMessage())
            );

            return 1;
        }

        return 0;
    }

    /**
     * Get the mapping purger.
     *
     * @return \Pim\Bundle\PrestashopConnectorBundle\Purger\DeltaTablePurger
     */
    protected function getDeltaTablePurger()
    {
        return $this->getContainer()->get('pim_prestashop_connector.purger.delta');
    }
}
