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

namespace B2bCode\Bundle\CmsFormBundle\Notification;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormResponse;

/**
 * Processes a CMS form response notification (e.g. sends an email).
 */
interface NotificationInterface
{
    /**
     * @param CmsFormResponse $formResponse
     * @param array<string, mixed> $context
     *
     * @return mixed
     */
    public function process(CmsFormResponse $formResponse, array $context = []);
}
