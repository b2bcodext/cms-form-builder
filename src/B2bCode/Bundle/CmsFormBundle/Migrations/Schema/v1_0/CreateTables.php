<?php

/*
 * This file is part of the B2Bcodext CMS Form Builder.
 *
 * (c) Daniel Nahrebecki <daniel@b2bcodext.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace B2bCode\Bundle\CmsFormBundle\Migrations\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @todo make it up to date with Installer
 */
class CreateTables implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createB2BCodeCmsFormTable($schema);
        $this->createB2BCodeCmsFormFieldTable($schema);

        /** Foreign keys generation **/
        $this->addB2BCodeCmsFormFieldForeignKeys($schema);
    }

    /**
     * Create b2b_code_cms_form table
     *
     * @param Schema $schema
     */
    protected function createB2BCodeCmsFormTable(Schema $schema)
    {
        $table = $schema->createTable('b2b_code_cms_form');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create b2b_code_cms_form_field table
     *
     * @param Schema $schema
     */
    protected function createB2BCodeCmsFormFieldTable(Schema $schema)
    {
        $table = $schema->createTable('b2b_code_cms_form_field');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('form_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('order', 'smallint', []);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['form_id'], 'idx_c32e75ca5ff69b7d', []);
    }

    /**
     * Add b2b_code_cms_form_field foreign keys.
     *
     * @param Schema $schema
     */
    protected function addB2BCodeCmsFormFieldForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('b2b_code_cms_form_field');
        $table->addForeignKeyConstraint(
            $schema->getTable('b2b_code_cms_form'),
            ['form_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
