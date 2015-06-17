<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Purger;

use Doctrine\ORM\EntityNotFoundException;

/**
 * Interface for purgers.
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
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
