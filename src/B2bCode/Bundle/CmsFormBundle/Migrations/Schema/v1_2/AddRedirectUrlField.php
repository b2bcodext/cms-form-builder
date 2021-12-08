<?php

namespace B2bCode\Bundle\CmsFormBundle\Migrations\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddRedirectUrlField implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addRedirectUrlField($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function addRedirectUrlField(Schema $schema)
    {
        $table = $schema->getTable('b2b_code_cms_form');
        $table->addColumn('redirect_url', 'string', ['length' => 1024, 'notnull' => false]);
    }
}
