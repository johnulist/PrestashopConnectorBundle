<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Reader\MongoDBODM;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityRepository;
use Pim\Bundle\BaseConnectorBundle\Reader\Doctrine\ODMProductReader;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\CatalogBundle\Manager\CompletenessManager;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use Pim\Bundle\CatalogBundle\Repository\ProductRepositoryInterface;
use Pim\Bundle\TransformBundle\Converter\MetricConverter;

/**
 * Delta product association reader for MongoDB
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class DeltaProductAssociationReader extends ODMProductReader
{
    /** @var EntityRepository */
    protected $deltaRepository;

    /**
     * @param ProductRepositoryInterface $repository
     * @param ChannelManager             $channelManager
     * @param CompletenessManager        $completenessManager
     * @param MetricConverter            $metricConverter
     * @param DocumentManager            $documentManager
     * @param boolean                    $missingCompleteness
     * @param EntityRepository           $deltaRepository
     */
    public function __construct(
        ProductRepositoryInterface $repository,
        ChannelManager $channelManager,
        CompletenessManager $completenessManager,
        MetricConverter $metricConverter,
        DocumentManager $documentManager,
        $missingCompleteness = true,
        EntityRepository $deltaRepository
    ) {
        parent::__construct(
            $repository,
            $channelManager,
            $completenessManager,
            $metricConverter,
            $documentManager,
            $missingCompleteness
        );

        $this->deltaRepository = $deltaRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        $this->documentManager->clear();

        if (!$this->executed) {
            $this->executed = true;
            if (!is_object($this->channel)) {
                $this->channel = $this->channelManager->getChannelByCode($this->channel);
            }

            if ($this->missingCompleteness) {
                $this->completenessManager->generateMissingForChannel($this->channel);
            }

            $this->query = $this->prepareQB()->getQuery();

            $this->products = $this->getQuery()->execute();

            // MongoDB Cursor are not positioned on first element (whereas ArrayIterator is)
            // as long as getNext() hasn't be called
            $this->products->getNext();
        }

        $result = $this->products->current();

        if ($result) {
            while (!$this->needsUpdate($result)) {
                $result = $this->products->getNext();
                if (null === $result) {
                    return null;
                }
            }

            $this->metricConverter->convert($result, $this->channel);
            $this->stepExecution->incrementSummaryInfo('read');
            $this->products->next();
        }

        return $result;
    }

    /**
     * @return \Doctrine\MongoDB\Query\Builder
     */
    protected function prepareQB()
    {
        $qb = $this->repository->buildByChannelAndCompleteness($this->channel);
        $qb->addAnd(
            $qb->expr()->field('associations.owner')->exists(true)
        );

        return $qb;
    }

    /**
     * @param ProductInterface $product
     *
     * @return bool
     */
    protected function needsUpdate(ProductInterface $product)
    {
        $delta = $this->deltaRepository->findOneBy(
            [
                'productId'   => $product->getId(),
                'jobInstance' => $this->stepExecution->getJobExecution()->getJobInstance(),
            ]
        );

        return null === $delta || $delta->getLastExport() < $product->getUpdated();
    }
}
