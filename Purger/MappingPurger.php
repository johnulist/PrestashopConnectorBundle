<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Purger;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Pim\Bundle\ImportExportBundle\Entity\Repository\JobInstanceRepository;

/**
 * Purge mapping from database helper.
 *
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
        $restUrl = $rawConfiguration['prestashopUrl'];
        foreach ($this->classesToPurge as $class) {
            $this->purgeMapping($restUrl, $class);
        }
    }

    /**
     * Execute purge.
     *
     * @param string $restUrl
     * @param string $class
     */
    protected function purgeMapping($restUrl, $class)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete($class, 'c')
            ->where($qb->expr()->eq('c.prestashopUrl', ':prestashopUrl'))
            ->setParameter(':prestashopUrl', $restUrl)
            ->getQuery()
            ->execute();
    }
}
