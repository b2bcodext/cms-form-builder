<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\EventListener\Datagrid;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFieldResponse;
use B2bCode\Bundle\CmsFormBundle\Entity\Repository\CmsFieldResponseRepository;
use B2bCode\Bundle\CmsFormBundle\EventListener\Datagrid\FormResponseListener;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FormResponseListenerTest extends TestCase
{
    private CmsFieldResponseRepository&MockObject $repository;
    private FormResponseListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->repository = $this->createMock(CmsFieldResponseRepository::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::any())
            ->method('getRepository')
            ->with(CmsFieldResponse::class)
            ->willReturn($this->repository);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects(self::any())
            ->method('getManagerForClass')
            ->with(CmsFieldResponse::class)
            ->willReturn($manager);

        $this->listener = new FormResponseListener($managerRegistry);
    }

    public function testTheFieldResponsesOfEveryRowAreLoadedInASingleQueryAndAttached(): void
    {
        $first = new ResultRecord(['id' => 11]);
        $second = new ResultRecord(['id' => 22]);
        $firstResponses = [new CmsFieldResponse()];
        $secondResponses = [new CmsFieldResponse(), new CmsFieldResponse()];

        $this->repository->expects(self::once())
            ->method('findGroupedByFormResponses')
            ->with([11, 22])
            ->willReturn([11 => $firstResponses, 22 => $secondResponses]);

        $this->listener->onResultAfter($this->event([$first, $second]));

        self::assertSame($firstResponses, $first->getValue('fieldResponses'));
        self::assertSame($secondResponses, $second->getValue('fieldResponses'));
    }

    public function testARowWithoutFieldResponsesIsLeftUntouched(): void
    {
        $record = new ResultRecord(['id' => 11]);
        $this->repository->method('findGroupedByFormResponses')->willReturn([]);

        $this->listener->onResultAfter($this->event([$record]));

        self::assertNull($record->getValue('fieldResponses'));
    }

    public function testAnEmptyResultStillAsksForNothing(): void
    {
        $this->repository->expects(self::once())
            ->method('findGroupedByFormResponses')
            ->with([])
            ->willReturn([]);

        $this->listener->onResultAfter($this->event([]));
    }

    /**
     * @param ResultRecord[] $records
     */
    private function event(array $records): OrmResultAfter
    {
        return new OrmResultAfter($this->createMock(DatagridInterface::class), $records);
    }
}
