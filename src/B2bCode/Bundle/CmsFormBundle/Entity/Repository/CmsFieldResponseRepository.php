<?php

/*
 * This file is part of the B2Bcodext CMS Form Builder.
 *
 * (c) Daniel Nahrebecki <daniel@b2bcodext.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace B2bCode\Bundle\CmsFormBundle\Entity\Repository;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFieldResponse;
use Doctrine\ORM\EntityRepository;

class CmsFieldResponseRepository extends EntityRepository
{
    /**
     * @param array $formResponsesIds
     * @return array
     */
    public function findGroupedByFormResponses(array $formResponsesIds = [])
    {
        /** @var CmsFieldResponse[] $records */
        $records = $this->findBy(['formResponse' => $formResponsesIds]);

        $groupedByResponses = [];
        foreach ($records as $record) {
            $responseId = $record->getFormResponse()->getId();
            if (array_key_exists($responseId, $groupedByResponses)) {
                $groupedByResponses[$responseId][] = $record;
            } else {
                $groupedByResponses[$responseId] = [$record];
            }
        }

        return $groupedByResponses;
    }
}
