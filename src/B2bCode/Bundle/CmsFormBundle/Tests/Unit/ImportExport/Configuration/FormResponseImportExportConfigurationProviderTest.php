<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\ImportExport\Configuration;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormResponse;
use B2bCode\Bundle\CmsFormBundle\ImportExport\Configuration\FormResponseImportExportConfigurationProvider;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use PHPUnit\Framework\TestCase;

class FormResponseImportExportConfigurationProviderTest extends TestCase
{
    public function testTheFormResponseExportIsConfiguredForItsOwnProcessorAndJob(): void
    {
        $configuration = (new FormResponseImportExportConfigurationProvider())->get();

        self::assertEquals(
            new ImportExportConfiguration([
                ImportExportConfiguration::FIELD_ENTITY_CLASS => CmsFormResponse::class,
                ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'b2b_code_cms_form_response',
                ImportExportConfiguration::FIELD_EXPORT_JOB_NAME => 'b2b_code_cms_form_responses_export_to_csv',
            ]),
            $configuration
        );
        self::assertSame(CmsFormResponse::class, $configuration->getEntityClass());
        self::assertSame('b2b_code_cms_form_response', $configuration->getExportProcessorAlias());
        self::assertSame('b2b_code_cms_form_responses_export_to_csv', $configuration->getExportJobName());
    }

    public function testNoImportIsConfiguredForFormResponses(): void
    {
        $configuration = (new FormResponseImportExportConfigurationProvider())->get();

        self::assertNull($configuration->getImportJobName());
        self::assertNull($configuration->getImportProcessorAlias());
    }
}
