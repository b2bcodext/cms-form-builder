<?php

/*
 * This file is part of the B2Bcodext CMS Form Builder.
 *
 * (c) Daniel Nahrebecki <daniel@b2bcodext.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace B2bCode\Bundle\CmsFormBundle\ImportExport\Configuration;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormResponse;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface;

class FormResponseImportExportConfigurationProvider implements ImportExportConfigurationProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function get(): ImportExportConfigurationInterface
    {
        return new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => CmsFormResponse::class,
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'b2b_code_cms_form_response',
            ImportExportConfiguration::FIELD_EXPORT_JOB_NAME => 'b2b_code_cms_form_responses_export_to_csv',
        ]);
    }
}
