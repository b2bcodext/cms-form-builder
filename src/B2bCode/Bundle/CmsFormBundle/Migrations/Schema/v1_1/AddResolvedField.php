<?php

namespace B2bCode\Bundle\CmsFormBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddResolvedField implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if (!$schema->hasTable('b2b_code_cms_form_response')) {
            return;
        }

        $table = $schema->getTable('b2b_code_cms_form_response');

        $table->addColumn('is_resolved', 'boolean', ['notnull' => false]);

        $queries->addPostQuery('UPDATE b2b_code_cms_form_response SET is_resolved = false');
    }
}
