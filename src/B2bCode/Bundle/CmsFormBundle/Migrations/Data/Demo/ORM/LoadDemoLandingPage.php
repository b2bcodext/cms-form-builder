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

namespace B2bCode\Bundle\CmsFormBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Oro\Bundle\CMSBundle\Migrations\Data\AbstractLoadPageData;

/**
 * Loads the demo landing page that embeds a CMS form.
 */
class LoadDemoLandingPage extends AbstractLoadPageData implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return [LoadDemoCmsForm::class];
    }

    /**
     * @return string
     */
    protected function getFilePaths(): string
    {
        return $this->getFilePathsFromLocator('@B2bCodeCmsFormBundle/Migrations/Data/Demo/ORM/data/pages.yml');
    }
}
