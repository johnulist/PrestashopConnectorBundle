<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Purger;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Pim\Bundle\ImportExportBundle\Entity\Repository\JobInstanceRepository;

/**
 * Purge mapping from database helper.
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MappingPurger implements PurgerInterface
{
    /** @var JobInstanceRepository */
    protected $jobInstanceRepository;

    /** @var EntityManager */
    protected $entityManager;

    /** @var array */
    protected $classesToPurge = [];

    /**
     * @param EntityManager         $entityManager
     * @param JobInstanceRepository $jobInstanceRepository
     */
    public function __construct(EntityManager $entityManager, JobInstanceRepository $jobInstanceRepository)
    {
        $this->entityManager         = $entityManager;
        $this->jobInstanceRepository = $jobInstanceRepository;
    }

    /**
     * Set mapping classes.
     *
     * @param array $mappingClasses
     */
    public function setClassesToPurge(array $classesToPurge)
    {
        $this->classesToPurge = $classesToPurge;
    }

    /**
     * Remove mapping from database.
     *
     * @param string $jobInstanceCode
     *
     * @throws EntityNotFoundException
     */
    public function purge($jobInstanceCode)
    {
        $jobInstance = $this->jobInstanceRepository->findOneByCode($jobInstanceCode);

        if (null === $jobInstance) {
            throw new EntityNotFoundException(
                sprintf('Job instance %s hasn\'t been found, verify your code.', $jobInstanceCode)
            );
        }

        $rawConfiguration = $jobInstance->getRawConfiguration();
        $soapUrl = $rawConfiguration['prestashopUrl'].$rawConfiguration['wsdlUrl'];
        foreach ($this->classesToPurge as $class) {
            $this->purgeMapping($soapUrl, $class);
        }
    }

    /**
     * Execute purge.
     *
     * @param string $soapUrl
     * @param string $class
     */
    protected function purgeMapping($soapUrl, $class)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete($class, 'c')
            ->where($qb->expr()->eq('c.prestashopUrl', ':prestashopUrl'))
            ->setParameter(':prestashopUrl', $soapUrl)
            ->getQuery()
            ->execute();
    }
}
