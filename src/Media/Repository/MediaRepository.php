<?php declare(strict_types=1);

namespace Shopware\Media\Repository;

use Shopware\Api\Read\BasicReaderInterface;
use Shopware\Api\RepositoryInterface;
use Shopware\Api\Search\AggregationResult;
use Shopware\Api\Search\Criteria;
use Shopware\Api\Search\SearcherInterface;
use Shopware\Api\Search\UuidSearchResult;
use Shopware\Api\Write\GenericWrittenEvent;
use Shopware\Api\Write\WriterInterface;
use Shopware\Context\Struct\TranslationContext;
use Shopware\Media\Event\MediaBasicLoadedEvent;
use Shopware\Media\Event\MediaWrittenEvent;
use Shopware\Media\Searcher\MediaSearchResult;
use Shopware\Media\Struct\MediaBasicCollection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MediaRepository implements RepositoryInterface
{
    /**
     * @var BasicReaderInterface
     */
    private $basicReader;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var SearcherInterface
     */
    private $searcher;

    /**
     * @var WriterInterface
     */
    private $writer;

    public function __construct(
        BasicReaderInterface $basicReader,
        EventDispatcherInterface $eventDispatcher,
        SearcherInterface $searcher,
        WriterInterface $writer
    ) {
        $this->basicReader = $basicReader;
        $this->eventDispatcher = $eventDispatcher;
        $this->searcher = $searcher;
        $this->writer = $writer;
    }

    public function readBasic(array $uuids, TranslationContext $context): MediaBasicCollection
    {
        if (empty($uuids)) {
            return new MediaBasicCollection();
        }

        /** @var MediaBasicCollection $collection */
        $collection = $this->basicReader->readBasic($uuids, $context);

        $this->eventDispatcher->dispatch(
            MediaBasicLoadedEvent::NAME,
            new MediaBasicLoadedEvent($collection, $context)
        );

        return $collection;
    }

    public function readDetail(array $uuids, TranslationContext $context): MediaBasicCollection
    {
        return $this->readBasic($uuids, $context);
    }

    public function search(Criteria $criteria, TranslationContext $context): MediaSearchResult
    {
        /** @var MediaSearchResult $result */
        $result = $this->searcher->search($criteria, $context);

        $this->eventDispatcher->dispatch(
            MediaBasicLoadedEvent::NAME,
            new MediaBasicLoadedEvent($result, $context)
        );

        return $result;
    }

    public function searchUuids(Criteria $criteria, TranslationContext $context): UuidSearchResult
    {
        return $this->searcher->searchUuids($criteria, $context);
    }

    public function aggregate(Criteria $criteria, TranslationContext $context): AggregationResult
    {
        $result = $this->searcher->aggregate($criteria, $context);

        return $result;
    }

    public function getEntityName(): string
    {
        return 'media';
    }

    public function update(array $data, TranslationContext $context): MediaWrittenEvent
    {
        $event = $this->writer->update($data, $context);

        $container = new GenericWrittenEvent($event, $context);
        $this->eventDispatcher->dispatch($container::NAME, $container);

        return $event;
    }

    public function upsert(array $data, TranslationContext $context): MediaWrittenEvent
    {
        $event = $this->writer->upsert($data, $context);

        $container = new GenericWrittenEvent($event, $context);
        $this->eventDispatcher->dispatch($container::NAME, $container);

        return $event;
    }

    public function create(array $data, TranslationContext $context): MediaWrittenEvent
    {
        $event = $this->writer->create($data, $context);

        $container = new GenericWrittenEvent($event, $context);
        $this->eventDispatcher->dispatch($container::NAME, $container);

        return $event;
    }
}
