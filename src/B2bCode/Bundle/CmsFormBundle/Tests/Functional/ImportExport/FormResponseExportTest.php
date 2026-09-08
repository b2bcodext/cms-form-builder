<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Functional\ImportExport;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * The "Export" button of the responses grid runs the bundle's own batch job. The job wires the
 * form-scoped reader, the export processor and the field-response normalizer together; only running
 * it shows whether that chain is still intact.
 *
 * @dbIsolationPerTest
 */
class FormResponseExportTest extends WebTestCase
{
    private const JOB_NAME = 'b2b_code_cms_form_responses_export_to_csv';
    private const PROCESSOR_ALIAS = 'b2b_code_cms_form_response';

    /** @var string[] file names written into the shared import/export storage by this test */
    private array $exportedFiles = [];

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures(['@B2bCodeCmsFormBundle/Tests/Functional/DataFixtures/cms_forms.yml']);
    }

    #[\Override]
    protected function tearDown(): void
    {
        foreach ($this->exportedFiles as $fileName) {
            $this->getFileManager()->deleteFile($fileName);
        }
        $this->exportedFiles = [];

        parent::tearDown();
    }

    public function testExportWritesTheResponsesOfTheRequestedFormOnly(): void
    {
        $result = $this->export($this->getCmsForm('preview-enabled'));

        self::assertTrue($result['success'], var_export($result['errors'], true));
        self::assertSame(0, $result['errorsCount'], var_export($result['errors'], true));
        self::assertSame(1, $result['readsCount']);

        $csv = $this->getFileManager()->getContent($result['file']);

        self::assertStringContainsString('"Field response 1 Field Name","Field response 1 Value"', $csv);
        self::assertStringContainsString(',preview-enabled,', $csv);
        // asserted pair by pair: CmsFormResponse::$fieldResponses carries no #[ORM\OrderBy], so the
        // column-group ORDER is not part of the contract and must not be pinned
        self::assertStringContainsString('last-name,NameDoe', $csv);
        self::assertStringContainsString('email,doe.xx@example.com', $csv);
        self::assertStringContainsString('contact-reason,"Have a complaint"', $csv);
        // the FieldResponseNormalizer exports the choice LABEL, never the stored value
        self::assertStringNotContainsString('contact-reason,complaint', $csv);
    }

    public function testExportOfAFormWithoutResponsesReadsNothing(): void
    {
        $result = $this->export($this->getCmsForm('preview-disabled'));

        self::assertTrue($result['success'], var_export($result['errors'], true));
        self::assertSame(0, $result['readsCount'], 'the form_id option really constrains the reader');

        self::assertStringNotContainsString('NameDoe', $this->getFileManager()->getContent($result['file']));
    }

    /**
     * @return array<string, mixed>
     */
    private function export(CmsForm $cmsForm): array
    {
        $result = $this->getExportHandler()->getExportResult(
            self::JOB_NAME,
            self::PROCESSOR_ALIAS,
            ProcessorRegistry::TYPE_EXPORT,
            'csv',
            null,
            ['form_id' => $cmsForm->getId()]
        );
        $this->exportedFiles[] = $result['file'];

        return $result;
    }

    private function getCmsForm(string $alias): CmsForm
    {
        $cmsForm = self::getContainer()->get('doctrine')->getRepository(CmsForm::class)
            ->findOneBy(['alias' => $alias]);
        self::assertInstanceOf(CmsForm::class, $cmsForm);

        return $cmsForm;
    }

    private function getExportHandler(): ExportHandler
    {
        return self::getContainer()->get('oro_importexport.handler.export');
    }

    private function getFileManager(): FileManager
    {
        return self::getContainer()->get('oro_importexport.file.file_manager');
    }
}
