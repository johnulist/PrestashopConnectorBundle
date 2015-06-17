<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Reader\ORM;

use Doctrine\ORM\EntityManager;
use Pim\Bundle\BaseConnectorBundle\Reader\Doctrine\ORMProductReader;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\CatalogBundle\Manager\CompletenessManager;
use Pim\Bundle\CatalogBundle\Repository\ProductRepositoryInterface;
use Pim\Bundle\PrestashopConnectorBundle\Builder\TableNameBuilder;
use Pim\Bundle\TransformBundle\Converter\MetricConverter;

/**
 * Delta product reader.
 *
 */
class DeltaProductReader extends ORMProductReader
{
    /** @var TableNameBuilder */
    protected $tableNameBuilder;

    /**
     * @param ProductRepositoryInterface $repository
     * @param ChannelManager             $channelManager
     * @param CompletenessManager        $completenessManager
     * @param MetricConverter            $metricConverter
     * @param EntityManager              $entityManager
     * @param boolean                    $missingCompleteness
     * @param TableNameBuilder           $tableNameBuilder
     */
    public function __construct(
        ProductRepositoryInterface $repository,
        ChannelManager $channelManager,
        CompletenessManager $completenessManager,
        MetricConverter $metricConverter,
        EntityManager $entityManager,
        $missingCompleteness = true,
        TableNameBuilder $tableNameBuilder
    ) {
        parent::__construct(
            $repository,
            $channelManager,
            $completenessManager,
            $metricConverter,
            $entityManager,
            $missingCompleteness
        );

        $this->tableNameBuilder = $tableNameBuilder;
    }

    /**
     * {@inheritdoc}
     */
    protected function getIds()
    {
        if (!is_object($this->channel)) {
            $this->channel = $this->channelManager->getChannelByCode($this->channel);
        }

        if ($this->missingCompleteness) {
            $this->completenessManager->generateMissingForChannel($this->channel);
        }

        $treeId = $this->channel->getCategory()->getId();
        $sql = $this->getSQLQuery($this->channel->getId(), $treeId, $this->getJobInstance()->getId());

        $connection = $this->entityManager->getConnection();
        $results = $connection->fetchAll($sql);

        $productIds = [];
        foreach ($results as $result) {
            $productIds[] = $result['id'];
        }

        return $productIds;
    }

    /**
     * @param int $channelId
     * @param int $treeId
     * @param int $jobInstanceId
     *
     * @return string
     */
    protected function getSQLQuery($channelId, $treeId, $jobInstanceId)
    {
        $productTable         = $this->tableNameBuilder->getTableName('pim_catalog.entity.product.class');
        $completenessesTable  = $this->tableNameBuilder->getTableName('pim_catalog.entity.completeness.class');
        $categoryProductTable = $this->tableNameBuilder->getTableName(
            'pim_catalog.entity.product.class',
            'categories'
        );
        $categoryTable     = $this->tableNameBuilder->getTableName('pim_catalog.entity.category.class');
        $deltaProductTable = $this->tableNameBuilder->getTableName(
            'pim_prestashop_connector.entity.delta_product_export.class'
        );

        return <<<SQL
            SELECT cp.id FROM $productTable cp

            INNER JOIN $completenessesTable comp
                ON comp.product_id = cp.id AND comp.channel_id = $channelId AND comp.ratio = 100

            INNER JOIN $categoryProductTable ccp ON ccp.product_id = cp.id
            INNER JOIN $categoryTable c
                ON c.id = ccp.category_id AND c.root = $treeId

            LEFT JOIN $deltaProductTable dpe ON dpe.product_id = cp.id
            LEFT JOIN akeneo_batch_job_instance j
                ON j.id = dpe.job_instance_id AND j.id = $jobInstanceId

            WHERE (cp.updated > dpe.last_export OR j.id IS NULL) AND cp.is_enabled = 1

            GROUP BY cp.id;
SQL;
    }

    /**
     * @return \Akeneo\Bundle\BatchBundle\Entity\JobInstance
     */
    protected function getJobInstance()
    {
        return $this->stepExecution->getJobExecution()->getJobInstance();
    }
}
