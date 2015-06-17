<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Purge mapping database for a given job instance code.
 *
 */
class PurgeMappingCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('prestashop-connector:mapping:purge')
            ->setDescription('Purges mapping from database for a given job instance code.')
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
        $mappingPurger   = $this->getMappingPurger();
        $jobInstanceCode = $input->getArgument('job_instance_code');

        $output->writeln(sprintf('<info>Executing command for "%s" job instance.<info>', $jobInstanceCode));

        try {
            $mappingPurger->purge($jobInstanceCode);
            $output->writeln(
                sprintf('<info>Mapping related to "%s" job instance has been purged.<info>', $jobInstanceCode)
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
     * @return \Pim\Bundle\PrestashopConnectorBundle\Purger\MappingPurger
     */
    protected function getMappingPurger()
    {
        return $this->getContainer()->get('pim_prestashop_connector.purger.mapping');
    }
}
