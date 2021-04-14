<?php

/*
 * This file is part of the B2Bcodext CMS Form Builder.
 *
 * (c) Daniel Nahrebecki <daniel@b2bcodext.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace B2bCode\Bundle\CmsFormBundle\EventListener\Datagrid;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFieldResponse;
use B2bCode\Bundle\CmsFormBundle\Entity\Repository\CmsFieldResponseRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;

class FormResponseListener
{
    /** @var ManagerRegistry */
    protected $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        $responseIds = [];
        foreach ($records as $record) {
            $responseIds[] = $record->getValue('id');
        }

        // get field responses
        $responses = $this->getRepository()->findGroupedByFormResponses($responseIds);

        foreach ($records as $record) {
            if (array_key_exists($record->getValue('id'), $responses)) {
                $record->setValue('fieldResponses', $responses[$record->getValue('id')]);
            }
        }
    }

    /**
     * @return CmsFieldResponseRepository
     */
    protected function getRepository()
    {
        return $this
            ->managerRegistry
            ->getManagerForClass(CmsFieldResponse::class)
            ->getRepository(CmsFieldResponse::class);
    }
}
