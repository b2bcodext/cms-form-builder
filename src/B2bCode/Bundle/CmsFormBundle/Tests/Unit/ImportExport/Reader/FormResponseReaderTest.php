<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\ImportExport\Reader;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormResponse;
use B2bCode\Bundle\CmsFormBundle\ImportExport\Reader\FormResponseReader;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Provider\ExportQueryProvider;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The reader narrows the standard entity export down to the responses of one CMS form; the assertion
 * therefore is on the DQL and the parameters of the query it hands to the export iterator.
 */
class FormResponseReaderTest extends TestCase
{
    private ?Query $builtQuery = null;
    private FormResponseReader $reader;

    #[\Override]
    protected function setUp(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::any())
            ->method('createQueryBuilder')
            ->willReturnCallback(static fn (): QueryBuilder => new QueryBuilder($entityManager));
        $entityManager->expects(self::any())->method('getExpressionBuilder')->willReturn(new Expr());
        $entityManager->expects(self::any())->method('getConfiguration')->willReturn(new Configuration());
        $entityManager->expects(self::any())
            ->method('getClassMetadata')
            ->willReturn(new ClassMetadata(CmsFormResponse::class));
        $entityManager->expects(self::any())
            ->method('getRepository')
            ->willReturnCallback(static fn (string $class): EntityRepository => new EntityRepository(
                $entityManager,
                new ClassMetadata($class)
            ));
        $entityManager->expects(self::any())
            ->method('createQuery')
            ->willReturnCallback(function (string $dql) use ($entityManager): Query {
                $this->builtQuery = (new Query($entityManager))->setDQL($dql);

                return $this->builtQuery;
            });

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects(self::any())->method('getManagerForClass')->willReturn($entityManager);

        $this->reader = new FormResponseReader(
            $this->createMock(ContextRegistry::class),
            $registry,
            $this->createMock(OwnershipMetadataProviderInterface::class),
            $this->createMock(ExportQueryProvider::class)
        );
    }

    public function testTheExportIsNarrowedToTheFormGivenInTheContext(): void
    {
        $this->reader->initializeByContext($this->context(7));

        self::assertStringContainsString('IDENTITY(o.form) = :form', $this->builtQuery->getDQL());
        self::assertSame(7, $this->builtQuery->getParameter('form')->getValue());
    }

    public function testWithoutAFormIdEveryResponseIsExported(): void
    {
        $this->reader->initializeByContext($this->context(null));

        self::assertStringNotContainsString('IDENTITY(o.form)', $this->builtQuery->getDQL());
        self::assertNull($this->builtQuery->getParameter('form'));
    }

    public function testTheFormFilterIsClearedAfterTheQueryIsBuiltSoItCannotLeakIntoTheNextExport(): void
    {
        $this->reader->initializeByContext($this->context(7));
        self::assertStringContainsString('IDENTITY(o.form) = :form', $this->builtQuery->getDQL());

        // Go through the public entry point that does NOT re-run initializeFromContext(): only the
        // `$this->formId = null` reset at the end of createSourceEntityQueryBuilder() can unfilter this one.
        $this->reader->setSourceEntityName(CmsFormResponse::class);

        self::assertStringNotContainsString('IDENTITY(o.form)', $this->builtQuery->getDQL());
    }

    /**
     * Characterization of a PRE-EXISTING defect, pinned rather than fixed (testing.md → flag it, don't change
     * it): the override declares `array $ids = []` but forwards only `$entityName` and `$organization` to
     * `EntityReader::createSourceEntityQueryBuilder()`, so the per-batch id restriction the async export sets
     * (`ImportExportBundle\Async\Export\PreExportMessageProcessor`) is dropped and every batch re-reads the
     * whole form. Flip this assertion when the forwarding is fixed.
     */
    public function testTheRequestedIdsAreCurrentlyDroppedInsteadOfNarrowingTheBatch(): void
    {
        $this->reader->setSourceEntityName(CmsFormResponse::class, null, [11, 22]);

        self::assertStringNotContainsString('IN (:ids)', $this->builtQuery->getDQL());
        self::assertNull($this->builtQuery->getParameter('ids'));
    }

    private function context(?int $formId): ContextInterface&MockObject
    {
        $context = $this->createMock(ContextInterface::class);
        $context->expects(self::any())->method('hasOption')->willReturnCallback(
            static fn (string $option): bool => $option === 'entityName'
        );
        $context->expects(self::any())->method('getOption')->willReturnCallback(
            static fn (string $option, mixed $default = null): mixed => match ($option) {
                'form_id'      => $formId,
                'entityName'   => CmsFormResponse::class,
                'organization' => null,
                default        => $default,
            }
        );

        return $context;
    }
}
