<?php

namespace Pim\Bundle\PrestashopConnectorBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Pim\Bundle\CatalogBundle\Event\ProductEvents;
use Doctrine\ORM\EntityManager;
use Pim\Bundle\PrestashopConnectorBundle\Builder\TableNameBuilder;

/**
 * Event subscriber on post remove and post mass remove products
 * Cascade remove delta tables from product ids.
 *
 */
class CascadeDeleteDeltaSubscriber implements EventSubscriberInterface
{
    /** @var EntityManager */
    protected $em;

    /** @var TableNameBuilder */
    protected $tableNameBuilder;

    /** @var string[] */
    protected $deltaEntities = [
        'pim_prestashop_connector.entity.delta_product_export.class',
        'pim_prestashop_connector.entity.delta_product_association_export.class',
        'pim_prestashop_connector.entity.delta_configurable_export.class',
    ];

    /**
     * @param EntityManager    $em
     * @param TableNameBuilder $tableNameBuilder
     */
    public function __construct(EntityManager $em, TableNameBuilder $tableNameBuilder)
    {
        $this->em = $em;
        $this->tableNameBuilder = $tableNameBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ProductEvents::POST_MASS_REMOVE => 'postMassRemove',
            ProductEvents::POST_REMOVE      => 'postRemove',
        ];
    }

    /**
     * Post remove action.
     *
     * @param GenericEvent $event
     */
    public function postRemove(GenericEvent $event)
    {
        $product = $event->getSubject();
        $this->removeCascade([$product->getId()]);
    }

    /**
     * Post mass remove action.
     *
     * @param GenericEvent $event
     */
    public function postMassRemove(GenericEvent $event)
    {
        $productIds = $event->getSubject();
        $this->removeCascade($productIds);
    }

    /**
     * @param array $productIds
     */
    protected function removeCascade(array $productIds)
    {
        $connection = $this->em->getConnection();
        foreach ($this->deltaEntities as $deltaEntity) {
            $deltaTable = $this->tableNameBuilder->getTableName($deltaEntity);
            $connection->executeQuery($this->getDeleteSQLQuery($productIds, $deltaTable));
        }
    }

    /**
     * @param array  $productIds
     * @param string $tableName
     *
     * @return string
     */
    protected function getDeleteSQLQuery(array $productIds, $tableName)
    {
        $productsList = implode(',', $productIds);

        return <<<SQL
DELETE FROM $tableName WHERE product_id IN ($productsList)
SQL;
    }
}
