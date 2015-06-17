<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Reader\ORM;

use Pim\Bundle\BaseConnectorBundle\Reader\ORM\EntityReader;

/**
 * Reads all entities at once.
 *
 */
class BulkEntityReader extends EntityReader
{
    /**
     * {@inheritdoc}
     */
    public function read()
    {
        $entities = [];
        while ($entity = parent::read()) {
            $entities[] = $entity;
        }

        return empty($entities) ? null : $entities;
    }
}
