<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Builder;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Get the table name from the entity parameter name
 * Ease overriding entities managing with DBAL support avoiding hard-coded table names.

 */
class TableNameBuilder
{
    /** @var ContainerInterface */
    protected $container;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /**
     * @param ContainerInterface $container
     * @param ManagerRegistry    $managerRegistry
     */
    public function __construct(ContainerInterface $container, ManagerRegistry $managerRegistry)
    {
        $this->container = $container;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * Get table name from container parameter defined.
     *
     * @param string $entityParameter
     * @param string $joinEntity
     *
     * @return string
     */
    public function getTableName($entityParameter, $joinEntity = null)
    {
        $entityClassName = $this->getEntityClassName($entityParameter);
        $classMetadata   = $this->getClassMetadata($entityClassName);

        if (null !== $joinEntity) {
            $assocMapping = $classMetadata->getAssociationMapping($joinEntity);
        }

        return isset($assocMapping['joinTable']['name']) ?
            $assocMapping['joinTable']['name'] : $classMetadata->getTableName();
    }

    /**
     * Returns class metadata for a defined entity parameter.
     *
     * @param string $entityClassName
     *
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    protected function getClassMetadata($entityClassName)
    {
        $manager = $this->managerRegistry->getManagerForClass($entityClassName);

        return $manager->getClassMetadata($entityClassName);
    }

    /**
     * Get the entity class name from its parameter.
     *
     * @param string $entityParameter
     *
     * @return mixed
     */
    protected function getEntityClassName($entityParameter)
    {
        return $this->container->getParameter($entityParameter);
    }
}
