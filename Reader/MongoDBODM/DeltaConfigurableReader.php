<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Reader\MongoDBODM;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityRepository;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\CatalogBundle\Manager\CompletenessManager;
use Pim\Bundle\CatalogBundle\Repository\ProductRepositoryInterface;
use Pim\Bundle\PrestashopConnectorBundle\Entity\Repository\GroupRepository;
use Pim\Bundle\TransformBundle\Converter\MetricConverter;

/**
 * Delta configurable reader for MongoDB.
 *
 */
class DeltaConfigurableReader extends DeltaProductReader
{
    /** @var GroupRepository */
    protected $groupRepository;

    /**
     * @param ProductRepositoryInterface $repository
     * @param ChannelManager             $channelManager
     * @param CompletenessManager        $completenessManager
     * @param MetricConverter            $metricConverter
     * @param DocumentManager            $documentManager
     * @param boolean                    $missingCompleteness
     * @param EntityRepository           $deltaRepository
     * @param GroupRepository            $groupRepository
     */
    public function __construct(
        ProductRepositoryInterface $repository,
        ChannelManager $channelManager,
        CompletenessManager $completenessManager,
        MetricConverter $metricConverter,
        DocumentManager $documentManager,
        $missingCompleteness = true,
        EntityRepository $deltaRepository,
        GroupRepository $groupRepository
    ) {
        parent::__construct(
            $repository,
            $channelManager,
            $completenessManager,
            $metricConverter,
            $documentManager,
            $missingCompleteness,
            $deltaRepository
        );

        $this->groupRepository = $groupRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareQB()
    {
        $variantGroupIds = $this->groupRepository->getVariantGroupIds();

        $qb = parent::prepareQB();
        $qb->addAnd(
            $qb->expr()->field('groupIds')->in($variantGroupIds)
        );

        return $qb;
    }
}
