<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Purger;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Pim\Bundle\ImportExportBundle\Entity\Repository\JobInstanceRepository;

/**
 * Purge delta table in terms of job instance.
 *
 */
class DeltaTablePurger implements PurgerInterface
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
     * {@inheritdoc}
     */
    public function setClassesToPurge(array $classesToPurge)
    {
        $this->classesToPurge = $classesToPurge;
    }

    /**
     * {@inheritdoc}
     */
    public function purge($jobInstanceCode)
    {
        $jobInstance = $this->jobInstanceRepository->findOneByCode($jobInstanceCode);

        if (null === $jobInstance) {
            throw new EntityNotFoundException(
                sprintf('Job instance %s hasn\'t been found, verify your code.', $jobInstanceCode)
            );
        }

        $jobId = $jobInstance->getId();
        foreach ($this->classesToPurge as $class) {
            $this->purgeDelta($jobId, $class);
        }
    }

    /**
     * Execute purge.
     *
     * @param int    $id
     * @param string $class
     */
    protected function purgeDelta($id, $class)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete($class, 'c')
            ->where($qb->expr()->eq('c.jobInstance', ':jobInstance'))
            ->setParameter(':jobInstance', $id)
            ->getQuery()
            ->execute();
    }
}
