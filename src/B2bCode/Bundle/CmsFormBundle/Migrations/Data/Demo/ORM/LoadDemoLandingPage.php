<?php

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

class LoadDemoLandingPage extends AbstractLoadPageData implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadDemoCmsForm::class];
    }

    /**
     * @return string
     */
    protected function getFilePaths()
    {
        return $this->getFilePathsFromLocator('@B2bCodeCmsFormBundle/Migrations/Data/Demo/ORM/data/pages.yml');
    }
}
