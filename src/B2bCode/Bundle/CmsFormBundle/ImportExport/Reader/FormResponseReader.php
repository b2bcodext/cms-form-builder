<?php

declare(strict_types=1);

/*
 * This file is part of the B2Bcodext CMS Form Builder.
 *
 * (c) Daniel Nahrebecki <daniel@b2bcodext.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace B2bCode\Bundle\CmsFormBundle\ImportExport\Reader;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Reader\EntityReader;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Reads the CMS form responses of one form for the import/export export job.
 */
class FormResponseReader extends EntityReader
{
    /** @var int|null */
    protected $formId;

    /**
     * @param string $entityName
     * @param int[] $ids
     * @return \Doctrine\ORM\QueryBuilder
     */
    #[\Override]
    protected function createSourceEntityQueryBuilder($entityName, ?Organization $organization = null, array $ids = [])
    {
        $qb = parent::createSourceEntityQueryBuilder($entityName, $organization);

        if ($this->formId > 0) {
            $aliases = $qb->getRootAliases();
            $rootAlias = reset($aliases);
            $qb
                ->andWhere(
                    $qb->expr()->eq(sprintf('IDENTITY(%s.form)', $rootAlias), ':form')
                )
                ->setParameter('form', $this->formId);
        }

        $this->formId = null;

        return $qb;
    }

    /**
     * @return void
     */
    #[\Override]
    protected function initializeFromContext(ContextInterface $context)
    {
        $this->formId = (int)$context->getOption('form_id');

        parent::initializeFromContext($context);
    }
}
