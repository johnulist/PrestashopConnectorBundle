<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Reader\ORM;

/**
 * Delta product association reader.
 *
 */
class DeltaProductAssociationReader extends DeltaProductReader
{
    /**
     * Left join with "pim_prestashop_delta_product_association_export"
     * instead of "pim_prestashop_delta_product_export".
     *
     * {@inheritdoc}
     */
    protected function getSQLQuery($channelId, $treeId, $jobInstanceId)
    {
        $productTable         = $this->tableNameBuilder->getTableName('pim_catalog.entity.product.class');
        $completenessesTable  = $this->tableNameBuilder->getTableName('pim_catalog.entity.completeness.class');
        $categoryProductTable = $this->tableNameBuilder->getTableName(
            'pim_catalog.entity.product.class',
            'categories'
        );
        $categoryTable         = $this->tableNameBuilder->getTableName('pim_catalog.entity.category.class');
        $deltaProductAssoTable = $this->tableNameBuilder->getTableName(
            'pim_prestashop_connector.entity.delta_product_association_export.class'
        );

        return <<<SQL
            SELECT cp.id FROM $productTable cp

            INNER JOIN $completenessesTable comp
                ON comp.product_id = cp.id AND comp.channel_id = $channelId AND comp.ratio = 100

            INNER JOIN $categoryProductTable ccp ON ccp.product_id = cp.id
            INNER JOIN $categoryTable c
                ON c.id = ccp.category_id AND c.root = $treeId

            INNER JOIN pim_catalog_association a ON cp.id = a.owner_id
            INNER JOIN pim_catalog_association_product ap ON a.id = ap.association_id

            LEFT JOIN $deltaProductAssoTable dpae ON dpae.product_id = cp.id
            LEFT JOIN akeneo_batch_job_instance j
                ON j.id = dpae.job_instance_id AND j.id = $jobInstanceId

            WHERE (cp.updated > dpae.last_export OR j.id IS NULL) AND cp.is_enabled = 1

            GROUP BY cp.id;
SQL;
    }
}
