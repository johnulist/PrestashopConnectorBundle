<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Purger;

use Doctrine\ORM\EntityNotFoundException;

/**
 * Interface for purgers.
 *
 */
interface PurgerInterface
{
    /**
     * Set classes to purge.
     *
     * @param array $classes
     */
    public function setClassesToPurge(array $classes);

    /**
     * Purge given database in terms of job instance code.
     *
     * @param string $jobInstanceCode
     *
     * @throws EntityNotFoundException
     */
    public function purge($jobInstanceCode);
}
