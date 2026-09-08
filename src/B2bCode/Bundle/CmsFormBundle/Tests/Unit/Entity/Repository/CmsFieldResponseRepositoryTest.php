<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Entity\Repository;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFieldResponse;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormResponse;
use B2bCode\Bundle\CmsFormBundle\Entity\Repository\CmsFieldResponseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EntityExtendBundle\Test\EntityExtendTestInitializer;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CmsFieldResponseRepositoryTest extends TestCase
{
    use EntityTrait;

    private EntityPersister&MockObject $persister;
    private CmsFieldResponseRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        EntityExtendTestInitializer::initialize();

        $this->persister = $this->createMock(EntityPersister::class);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects(self::any())
            ->method('getEntityPersister')
            ->with(CmsFieldResponse::class)
            ->willReturn($this->persister);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::any())->method('getUnitOfWork')->willReturn($unitOfWork);

        $this->repository = new CmsFieldResponseRepository(
            $entityManager,
            new ClassMetadata(CmsFieldResponse::class)
        );
    }

    public function testTheRecordsAreLookedUpByTheGivenFormResponseIds(): void
    {
        $this->persister->expects(self::once())
            ->method('loadAll')
            ->with(['formResponse' => [11, 22]], null, null, null)
            ->willReturn([]);

        self::assertSame([], $this->repository->findGroupedByFormResponses([11, 22]));
    }

    public function testTheRecordsAreGroupedByTheirOwningFormResponse(): void
    {
        $first = $this->fieldResponse(11, 'email', 'text');
        $second = $this->fieldResponse(11, 'message', 'textarea');
        $third = $this->fieldResponse(22, 'email', 'text');
        $this->persister->method('loadAll')->willReturn([$first, $second, $third]);

        self::assertSame(
            [11 => [$first, $second], 22 => [$third]],
            $this->repository->findGroupedByFormResponses([11, 22])
        );
    }

    public function testTheRecaptchaFieldIsNeverPartOfADisplayedResponse(): void
    {
        $visible = $this->fieldResponse(11, 'email', 'text');
        $captcha = $this->fieldResponse(11, 'captcha', 'oro-recaptcha-v3');
        $this->persister->method('loadAll')->willReturn([$captcha, $visible]);

        self::assertSame([11 => [$visible]], $this->repository->findGroupedByFormResponses([11]));
    }

    public function testAResponseMadeOnlyOfRecaptchaFieldsIsAbsentFromTheResult(): void
    {
        $this->persister->method('loadAll')->willReturn([$this->fieldResponse(11, 'captcha', 'oro-recaptcha-v3')]);

        self::assertSame([], $this->repository->findGroupedByFormResponses([11]));
    }

    public function testNoIdsYieldsNoGroups(): void
    {
        $this->persister->expects(self::once())
            ->method('loadAll')
            ->with(['formResponse' => []], null, null, null)
            ->willReturn([]);

        self::assertSame([], $this->repository->findGroupedByFormResponses());
    }

    private function fieldResponse(int $formResponseId, string $fieldName, string $fieldType): CmsFieldResponse
    {
        /** @var CmsFormResponse $formResponse */
        $formResponse = $this->getEntity(CmsFormResponse::class, ['id' => $formResponseId]);

        return (new CmsFieldResponse())
            ->setField((new CmsFormField())->setName($fieldName)->setType($fieldType))
            ->setFormResponse($formResponse);
    }
}
