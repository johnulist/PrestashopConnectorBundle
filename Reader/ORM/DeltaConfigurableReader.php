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
 * Delta reader for configurables.
 *
 * @author    Romain Monceau <romain@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class DeltaConfigurableReader extends ORMProductReader
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
        $groupTable        = $this->tableNameBuilder->getTableName('pim_catalog.entity.group.class');
        $groupProductTable = $this->tableNameBuilder->getTableName('pim_catalog.entity.product.class', 'groups');
        $groupTypeTable    = $this->tableNameBuilder->getTableName('pim_catalog.entity.group_type.class');
        $categoryTable     = $this->tableNameBuilder->getTableName('pim_catalog.entity.category.class');
        $deltaConfigurableTable = $this->tableNameBuilder->getTableName(
            'pim_prestashop_connector.entity.delta_configurable_export.class'
        );

        return <<<SQL
            SELECT p.id FROM $productTable p
            INNER JOIN $completenessesTable comp
                ON comp.product_id = p.id AND comp.channel_id = $channelId AND comp.ratio = 100
            INNER JOIN $categoryProductTable cp ON p.id = cp.product_id
            INNER JOIN $categoryTable c ON c.id = cp.category_id AND c.root = $treeId

            INNER JOIN $groupProductTable gp ON gp.product_id = p.id
            INNER JOIN $groupTable g ON g.id = gp.group_id
            INNER JOIN $groupTypeTable gt ON gt.id = g.type_id AND gt.is_variant = 1

            LEFT JOIN $deltaConfigurableTable de ON de.product_id = p.id
            LEFT JOIN akeneo_batch_job_instance j ON j.id = de.job_instance_id AND j.id = $jobInstanceId

            WHERE p.updated > de.last_export OR j.id IS NULL
            AND p.is_enabled = 1

            GROUP BY p.id
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
