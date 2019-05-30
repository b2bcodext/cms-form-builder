<?php

/*
 * This file is part of the B2Bcodext CMS Form Builder.
 *
 * (c) Daniel Nahrebecki <daniel@b2bcodext.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace B2bCode\Bundle\CmsFormBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @todo indexes, notnull, onDelete, etc.!
 */
class B2bCodeCmsFormBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createB2BCodeCmsFormResponseTable($schema);
        $this->createB2BCodeCmsFieldResponseTable($schema);
        $this->createB2BCodeCmsFormFieldTable($schema);
        $this->createB2BCodeCmsFormNotificationTable($schema);
        $this->createB2BCodeCmsFormTable($schema);

        /** Foreign keys generation **/
        $this->addB2BCodeCmsFormResponseForeignKeys($schema);
        $this->addB2BCodeCmsFieldResponseForeignKeys($schema);
        $this->addB2BCodeCmsFormFieldForeignKeys($schema);
        $this->addB2BCodeCmsFormNotificationForeignKeys($schema);
    }

    /**
     * Create b2b_code_cms_form_response table
     *
     * @param Schema $schema
     */
    protected function createB2BCodeCmsFormResponseTable(Schema $schema)
    {
        $table = $schema->createTable('b2b_code_cms_form_response');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('form_id', 'integer', []);
        $table->addColumn('visitor_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('serialized_data', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['visitor_id'], 'idx_eab5a5270bee6d', []);
        $table->addIndex(['form_id'], 'idx_eab5a525ff69b7d', []);
    }

    /**
     * Create b2b_code_cms_field_response table
     *
     * @param Schema $schema
     */
    protected function createB2BCodeCmsFieldResponseTable(Schema $schema)
    {
        $table = $schema->createTable('b2b_code_cms_field_response');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('field_id', 'integer', []);
        $table->addColumn('form_response_id', 'integer', []);
        $table->addColumn('value', 'text', ['notnull' => false]);
        $table->addColumn('serialized_data', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['form_response_id'], 'idx_3679f827c98b851', []);
        $table->addIndex(['field_id'], 'idx_3679f827443707b0', []);
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
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addColumn('sort_order', 'smallint', []);
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->addColumn('options', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('serialized_data', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addIndex(['form_id'], 'idx_c32e75ca5ff69b7d', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['form_id', 'name'], 'uidx_b2b_code_field_form_name');
    }

    /**
     * Create b2b_code_cms_form_notification table
     *
     * @param Schema $schema
     */
    protected function createB2BCodeCmsFormNotificationTable(Schema $schema)
    {
        $table = $schema->createTable('b2b_code_cms_form_notification');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('form_id', 'integer', []);
        $table->addColumn('template_id', 'integer', ['notnull' => false]);
        $table->addColumn('email', 'string', ['notnull' => false, 'length' => 255]);
        $table->addIndex(['form_id'], 'idx_45a891715ff69b7d', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['template_id'], 'idx_45a891715da0fb8', []);
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
        $table->addColumn('alias', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('serialized_data', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('uuid', 'string', ['length' => 255]);
        $table->addColumn('preview_enabled', 'boolean', ['notnull' => false]);
        $table->addColumn('notifications_enabled', 'boolean', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['alias'], 'uniq_e042b376e16c6b94');
        $table->addUniqueIndex(['uuid'], 'uniq_e042b376d17f50a6');
    }

    /**
     * Add b2b_code_cms_form_response foreign keys.
     *
     * @param Schema $schema
     */
    protected function addB2BCodeCmsFormResponseForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('b2b_code_cms_form_response');
        $table->addForeignKeyConstraint(
            $schema->getTable('b2b_code_cms_form'),
            ['form_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_visitor'),
            ['visitor_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add b2b_code_cms_field_response foreign keys.
     *
     * @param Schema $schema
     */
    protected function addB2BCodeCmsFieldResponseForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('b2b_code_cms_field_response');
        $table->addForeignKeyConstraint(
            $schema->getTable('b2b_code_cms_form_field'),
            ['field_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('b2b_code_cms_form_response'),
            ['form_response_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
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

    /**
     * Add b2b_code_cms_form_notification foreign keys.
     *
     * @param Schema $schema
     */
    protected function addB2BCodeCmsFormNotificationForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('b2b_code_cms_form_notification');
        $table->addForeignKeyConstraint(
            $schema->getTable('b2b_code_cms_form'),
            ['form_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_template'),
            ['template_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
