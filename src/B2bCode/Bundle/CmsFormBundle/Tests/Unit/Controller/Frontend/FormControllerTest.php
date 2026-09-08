<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Controller\Frontend;

use B2bCode\Bundle\CmsFormBundle\Controller\Frontend\FormController;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use PHPUnit\Framework\TestCase;

class FormControllerTest extends TestCase
{
    public function testThePreviewLayoutIsFedTheFormAsItsEntity(): void
    {
        $cmsForm = (new CmsForm())->setName('Contact us');

        self::assertSame(
            ['data' => ['entity' => $cmsForm]],
            (new FormController())->formViewAction($cmsForm)
        );
    }
}
